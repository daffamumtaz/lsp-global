<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CertificationStats;
use App\Filament\Widgets\UnreadMessages;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class Dashboard extends BaseDashboard
{
    use HasWidgetGrid {
        sanitizeWidgetGridItems as private normalizeLayout;
    }

    public const WIDGETS = [CertificationStats::class, UnreadMessages::class];

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-admin'), 403);
    }

    public function getWidgetsContentComponent(): Component
    {
        return View::make('filament.pages.dashboard');
    }

    public function getWidgets(): array
    {
        return self::WIDGETS;
    }

    protected function sanitizeWidgetGridItems(array $items, ?array $allowed = null): array
    {
        if (count($items) > count(self::WIDGETS)) {
            throw ValidationException::withMessages(['widgetGridLayout' => 'Maksimal dua widget.']);
        }
        $validator = Validator::make(['widgetGridLayout' => $items], [
            'widgetGridLayout' => ['array', 'max:2'],
            'widgetGridLayout.*' => ['array:widget,x,y,w,h,visible'],
            'widgetGridLayout.*.widget' => ['required', 'string', 'distinct', Rule::in($allowed ?? $this->allowedWidgetClasses())],
            'widgetGridLayout.*.x' => ['required', 'integer', 'between:0,11'],
            'widgetGridLayout.*.y' => ['required', 'integer', 'between:0,240'],
            'widgetGridLayout.*.w' => ['required', 'integer', 'between:1,12'],
            'widgetGridLayout.*.h' => ['required', 'integer', 'between:1,60'],
            'widgetGridLayout.*.visible' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages(['widgetGridLayout' => 'Layout tidak valid. Pilih widget yang tersedia dan ukuran dalam batas.']);
        }

        return $this->normalizeLayout($items, $allowed);
    }

    protected function itemsFromStorage(mixed $stored, ?array $allowed = null): array
    {
        $items = is_array($stored) ? ($stored['items'] ?? $stored) : [];
        if (! is_array($items) || $items === []) {
            return $this->defaultPackedLayout($allowed ?? $this->allowedWidgetClasses());
        }
        try {
            return $this->sanitizeWidgetGridItems($items, $allowed);
        } catch (ValidationException) {
            return $this->defaultPackedLayout($allowed ?? $this->allowedWidgetClasses());
        }
    }

    protected function getAdditionalHeaderActions(): array
    {
        return [
            Action::make('customize')->label('Atur dashboard')->visible(fn () => ! $this->widgetGridEditing)
                ->authorize('manage-admin')->action(fn () => $this->keepWidgetGridEditing()),
            Action::make('accessibleLayout')->label('Atur urutan dan ukuran')->modalSubmitActionLabel('Simpan layout pribadi')->authorize('manage-admin')
                ->fillForm(fn () => ['items' => $this->widgetGridLayout])
                ->schema([
                    Repeater::make('items')->label('Widget')->addable(false)->deletable(false)
                        ->reorderableWithButtons()->reorderableWithDragAndDrop(false)->schema([
                            Select::make('widget')->label('Widget')->options(collect(self::WIDGETS)->mapWithKeys(fn ($widget) => [$widget => app($widget)->getTitle()])->all())->disabled()->dehydrated(),
                            Select::make('w')->label('Lebar')->options([6 => 'Setengah', 12 => 'Penuh'])->required(),
                            TextInput::make('h')->label('Tinggi')->integer()->minValue(1)->maxValue(60)->required(),
                        ]),
                ])->action(function (array $data): void {
                    $y = 0;
                    $items = [];
                    foreach ($data['items'] as $item) {
                        $items[] = [...$item, 'x' => 0, 'y' => $y, 'visible' => true];
                        $y += (int) $item['h'];
                    }
                    $this->syncWidgetGridLayout($items);
                    $this->saveWidgetGridLayout();
                }),
        ];
    }
}
