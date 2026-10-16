<?php

namespace Tests\Feature;

use App\Models\Process;
use App\Models\ProcessStage;
use App\Models\ProcessStep;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class ProcessReorderConcurrencyTest extends TestCase
{
    public function test_reordering_waits_for_the_parent_without_blocking_an_existing_editor(): void
    {
        if (config('database.default') !== 'pgsql' || ! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
            $this->markTestSkipped('Requires PostgreSQL, pcntl, and posix for two concurrent connections.');
        }

        $this->artisan('migrate:fresh', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
        RefreshDatabaseState::$migrated = false;
        $stage = ProcessStage::factory()->create();
        $first = ProcessStep::factory()->create(['stage_id' => $stage->id, 'order' => 1]);
        $second = ProcessStep::factory()->create(['stage_id' => $stage->id, 'order' => 2]);
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertNotFalse($sockets);
        DB::disconnect();
        $worker = pcntl_fork();
        $this->assertNotSame(-1, $worker);

        if ($worker === 0) {
            fclose($sockets[0]);
            stream_set_timeout($sockets[1], 10);
            DB::purge();
            try {
                DB::statement("SET statement_timeout = '8s'");
                fwrite($sockets[1], DB::selectOne('SELECT pg_backend_pid() AS pid')->pid."\n");
                if (trim((string) fgets($sockets[1])) !== 'reorder') {
                    throw new RuntimeException('The editor did not release the reorder worker.');
                }
                ProcessStep::reorderGroup(['stage_id' => $stage->id, 'actor_id' => null], [$second->id, $first->id]);
                fwrite($sockets[1], json_encode(['success' => true], JSON_THROW_ON_ERROR)."\n");
            } catch (Throwable $error) {
                fwrite($sockets[1], json_encode(['error' => $error->getMessage()], JSON_THROW_ON_ERROR)."\n");
            }
            DB::disconnect();
            fclose($sockets[1]);
            exit(0);
        }

        fclose($sockets[1]);
        stream_set_timeout($sockets[0], 10);
        try {
            $backendId = (int) fgets($sockets[0]);
            $this->assertGreaterThan(0, $backendId);
            DB::statement("SET statement_timeout = '8s'");
            DB::beginTransaction();
            Process::query()->whereKey($stage->process_id)->lockForUpdate()->firstOrFail();
            fwrite($sockets[0], "reorder\n");

            $deadline = microtime(true) + 5;
            do {
                $waiting = DB::selectOne('SELECT wait_event_type FROM pg_stat_activity WHERE pid = ?', [$backendId]);
                if ($waiting?->wait_event_type === 'Lock') {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertSame('Lock', $waiting?->wait_event_type, 'The reorder worker did not wait for the editor transaction.');

            $first->update(['title' => 'Edited while reorder waits']);
            DB::commit();
            $result = json_decode((string) fgets($sockets[0]), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(['success' => true], $result);
            $this->assertSame('Edited while reorder waits', $first->refresh()->title);
            $this->assertSame(2, $first->order);
            $this->assertSame(1, $second->refresh()->order);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::statement('SET statement_timeout = 0');
            fclose($sockets[0]);
            $deadline = microtime(true) + 2;
            do {
                $finished = pcntl_waitpid($worker, $status, WNOHANG);
                if ($finished !== 0) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            if ($finished === 0) {
                posix_kill($worker, SIGKILL);
                pcntl_waitpid($worker, $status);
            }
            RefreshDatabaseState::$migrated = false;
        }
    }
}
