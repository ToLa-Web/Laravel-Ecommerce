<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\ProductVariationTypeEnum;
use App\Filament\Resources\ProductResource;
use App\Models\VariationType;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\options;

class ProductVariations extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $navigationIcon = 'hugeicons-validation-approval';

    protected static ?string $title = 'Variation';

    public function form(Form $form): Form
    {
        $types = $this->record->variationTypes;
        $fileds = [];
        foreach ($types as $type) {
            $fileds[] = TextInput::make('variation_type_' . $type->id . '.id')
                ->hidden()
                ->default('');
            $fileds[] = TextInput::make('variation_type_' . $type->id . '.name')
                ->label($type->name)
                ->default('');
        }
        return $form
            ->schema([
                  Repeater::make('variations')
                    ->label(false)
                    ->collapsible()
                    ->addable(false)
                    ->defaultItems(1)
                    ->schema([
                        Section::make()
                            ->schema($fileds)
                            ->columns(3),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric(),
                        
                    ])
                    ->columns(2)
                    ->columnSpan(2)

            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Load data before showing form - combines existing data with all possible combinations
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $variations = $this->record->variations->toArray();

        $data['variations'] = $this->mergeCartesianWithExisting($this->record->variationTypes, $variations);

        return $data;
    }

    // Merge saved variations with all possible combinations
    private function mergeCartesianWithExisting($variationTypes, $existingData)
    {
        $defaultQuantity = $this->record->quantity;
        $defaultPrice = $this->record->price;
        $cartesianProduct = $this->cartesianProduct($variationTypes, $defaultPrice, $defaultQuantity);
        $mergeResult = [];

        foreach ($cartesianProduct as $product)
        {
            // Get option IDs for this combination
            $optionIds = collect($product)
                ->filter(fn($value, $key) => str_starts_with($key, 'variation_type_'))
                ->map(fn($option) => $option['id'])
                ->values()
                ->toArray();

            // Check if combination already exists in database
            $match = array_filter($existingData, function($existingOption) use ($optionIds){
                return $existingOption['variation_type_option_ids'] === $optionIds; 
            });

            if (!empty($match)) {
                // Use existing quantity/price
                $existingEntry = reset($match);
                $product['quantity'] = $existingEntry['quantity'];
                $product['price'] = $existingEntry['price'];
            }else{
                // Use default values for new combinations
                $product['quantity'] = $defaultQuantity;
                $product['price'] = $defaultPrice;
            }

            $mergeResult[] = $product;
        }

        return $mergeResult;
    }
    
    // Create all possible combinations (Size S+Color Red, Size S+Color Blue, etc.)
    private function cartesianProduct($variationTypes, $defaultQuantity = null, $defaultPrice = null): array
    {
        $result = [[]];

        foreach ($variationTypes as $index => $variationType){
          $temp = [];
          
          foreach ($variationType->options as $option){

            foreach ($result as $combination){
                $newCombination = $combination + [
                    'variation_type_' . ($variationType->id) => [
                        'id' => $option->id,
                        'name' => $option->name,
                        'label' => $variationType->name,
                    ]
                ];
                $temp[] = $newCombination;
            };

          };   
          $result = $temp;

        };
        foreach ($result as &$combination){
            if (count($combination) === count($variationTypes)){
                $combination['quantity'] = $defaultQuantity;
                $combination['price'] = $defaultPrice;
            }
        }
        return $result;
        
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     $formattedData = [];

    //     foreach ($data['variations'] as $option)
    //     {
    //         $variationTypeOptionIds = [];
    //         foreach ($this->record->variationTypes as $variationType){
    //             $variationTypeOptionIds[] = $option['variation_type_' . ($variationType->id)]['id'];
    //         }
            
    //         $quantity = $option['quantity'];
    //         $price = $option['price'];

    //         $formattedData[] = [
    //             'variation_type_option_ids' => $variationTypeOptionIds,
    //             'quantity' => $quantity,
    //             'price' => $price
    //         ];
    //     }
    //     $data['variations'] = $formattedData;
    //     return $data;

    // }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $formattedData = [];

        foreach ($data['variations'] ?? [] as $index => $option) {
            $variationTypeOptionIds = [];
            
            foreach ($this->record->variationTypes as $variationType) {
                $fieldKey = 'variation_type_' . $variationType->id;
                
                if (isset($option[$fieldKey])) {
                    // Check if we have the ID directly
                    if (isset($option[$fieldKey]['id']) && !empty($option[$fieldKey]['id'])) {
                        $variationTypeOptionIds[] = $option[$fieldKey]['id'];
                    } 
                    // If no ID, try to find it by name
                    elseif (isset($option[$fieldKey]['name'])) {
                        $optionName = $option[$fieldKey]['name'];
                        $foundOption = $variationType->options->firstWhere('name', $optionName);
                        if ($foundOption) {
                            $variationTypeOptionIds[] = $foundOption->id;
                        }
                    }
                }
            }
            
            $quantity = $option['quantity'] ?? 0;
            $price = $option['price'] ?? 0;

            if (!empty($variationTypeOptionIds)) {
                $formattedData[] = [
                    'variation_type_option_ids' => $variationTypeOptionIds,
                    'quantity' => $quantity,
                    'price' => $price
                ];
            }
        }
        
        $data['variations'] = $formattedData;
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $variations = $data['variations'];
        unset($data['variations']);

        $record->update($data);
        $record->variations()->delete();
        $record->variations()->createMany($variations);

        return $record;
    }
}