<?php

namespace Tests\Feature;

use App\Filament\Pages\Identity;
use App\Filament\Resources\CertificationSchemes\Pages\ManageCertificationSchemes;
use App\Filament\Resources\GalleryItems\Pages\ManageGalleryItems;
use App\Models\CertificationScheme;
use App\Models\GalleryItem;
use App\Models\SitePage;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_formatted_rupiah_saves_as_integer_and_invalid_levels_are_rejected(): void
    {
        $scheme = CertificationScheme::factory()->create(['methods' => ['luring']]);
        $editor = Livewire::test(ManageCertificationSchemes::class);
        $this->assertSame('edit', $editor->instance()->getTable()->getRecordAction($scheme));
        $editor->callAction(TestAction::make('edit')->table($scheme), ['price' => '1.500.000', 'level' => 9])->assertHasNoActionErrors();
        $this->assertSame(1500000, $scheme->fresh()->price);
        $editor->callAction(TestAction::make('edit')->table($scheme), ['price' => '0', 'level' => 1])->assertHasNoActionErrors();
        $this->assertSame(0, $scheme->fresh()->price);
        $editor->callAction(TestAction::make('edit')->table($scheme), ['price' => null])->assertHasNoActionErrors();
        $this->assertNull($scheme->fresh()->price);
        foreach ([0, 10] as $level) {
            $editor->callAction(TestAction::make('edit')->table($scheme), ['level' => $level])->assertHasActionErrors(['level']);
            $editor->call('unmountAction');
        }
        $this->assertEquals(1, $scheme->fresh()->level);
    }

    public function test_gallery_table_uses_only_first_file_and_editor_previews_all_saved_files(): void
    {
        Storage::fake('public');
        $album = GalleryItem::factory()->create(['media_type' => 'photo']);
        $first = $album->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('gallery');
        $second = $album->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('gallery');
        $album->refresh();
        $editor = Livewire::test(ManageGalleryItems::class)->assertSee($first->getUrl(), false)->assertDontSee($second->getUrl(), false);
        $this->assertSame('edit', $editor->instance()->getTable()->getRecordAction($album));
        $editor->mountAction(TestAction::make('edit')->table($album))->assertActionMounted(TestAction::make('edit')->table($album))
            ->assertActionDataSet(['image' => [$first->uuid, $second->uuid]]);
        $this->assertCount(2, $album->fresh()->getMedia('gallery'));
    }

    public function test_branding_upload_displays_saved_image_and_upload_guidance(): void
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs('identity', UploadedFile::fake()->image('logo.png'), 'logo.png');
        SitePage::query()->create(['kind' => 'identity', 'title' => 'Identitas', 'content' => ['logo_path' => 'identity/logo.png']]);
        Livewire::test(Identity::class)->assertSee('Media tersimpan')->assertSee('logo.png')->assertSee('800 × 400 px')->assertSee('rasio 2:1');
    }
}
