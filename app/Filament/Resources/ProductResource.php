<?php

namespace App\Filament\Resources;

use App\Enums\Enums\ProductStatusEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Resources\ProductResource\Pages\ProductImages;
use App\Models\Department;
use App\Models\Product;
use App\RolesEnum;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Grid layout for grouping related fields
                Forms\Components\Grid::make()
                    ->schema([
                        // Product Title input
                        TextInput::make('title')
                            ->live(onBlur: true) // Updates on blur (when focus leaves the field)
                            ->required() // Field is mandatory
                            // Automatically generate slug when title changes
                            ->afterStateUpdated(
                                function (string $operation, $state, callable $set) {
                                    $set('slug', Str::slug($state)); // Converts title to URL-friendly slug
                                }
                            ),
                        // Slug input (auto-filled from title, but can be edited)
                        TextInput::make('slug')
                            ->required(),
                        // Department dropdown (populated from related departments)
                        Select::make('department_id')
                            ->relationship('department', 'name') // Uses 'name' from related Department
                            ->label(__('Department'))
                            ->preload() // Loads options in advance for performance
                            ->searchable() // Allows searching in dropdown
                            ->required()
                            ->reactive() // Reacts to changes (triggers afterStateUpdated)
                            // When department changes, reset category selection
                            ->afterStateUpdated(function (callable $set, $state) {
                                $set('category_id', null);
                            }),
                        // Category dropdown (filtered by selected department)
                        Select::make('category_id')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, callable $get) {
                                    $departmentId = $get('department_id');
                                    if ($departmentId) {
                                        $query->where('department_id', $departmentId); // Only show categories for selected department
                                    }
                                }
                            )
                            ->label(__('Category'))
                            ->preload()
                            ->searchable()
                            ->required(),
                    ]),
                // Rich text editor for product description
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'link', 'h2', 'h3', 'orderedList',
                        'redo', 'strike', 'undo', 'underline', 'table', 'bulletList',
                        'numberedList', 'blockquote', 'codeBlock',
                    ])
                    ->columnSpan(2), // Spans two columns in the grid
                // Price input (must be numeric)
                TextInput::make('price')
                    ->required()
                    ->numeric(),
                // Quantity input (must be integer)
                TextInput::make('quantity')
                    ->integer(),
                // Status dropdown (uses enum labels and defaults to Draft)
                Select::make('status')
                    ->options(ProductStatusEnum::labels())
                    ->default(ProductStatusEnum::Draft->value)
                    ->required()
            ]);
    }
    /**
     * Define the table configuration for the Product resource in Filament.
     *
     * - Displays columns for product title, status, department, category, and creation date.
     * - Allows sorting and searching by product title.
     * - Shows status as a colored badge using values from ProductStatusEnum.
     * - Displays related department and category names.
     * - Formats the creation date as date and time.
     * - Provides filters for status and department using select dropdowns.
     * - Enables editing of individual products.
     * - Supports bulk deletion of selected products.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->words(10)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors(ProductStatusEnum::colors()),
                Tables\Columns\TextColumn::make('department.name'),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ProductStatusEnum::labels()),
                SelectFilter::make('department_id')
                    ->relationship('department', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' => Pages\ProductImages::route('/{record}/images')
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return 
            $page->generateNavigationItems([
                EditProduct::class,
                ProductImages::class
            ]);
    }

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole(RolesEnum::Vendor);
    }
}
