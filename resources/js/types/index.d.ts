import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type Image = {
    id: number;
    thumb: string;
    small: string;
    large: string;
}

export type VariationTypeOption = {
    id: number;
    name: string;
    images: Image[];
    type: VariationType
}

export type variationType = {
    id: number;
    name: string;
    type: 'select' | 'Radio' | 'Image';
    options: VariationTypeOption[] 
}

export type Product = {
    id: number;
    title: string;
    slug: string;
    price: number;
    quantity: number;
    image: string;
    images: Image[];
    description: string;
    short_description: string;
    user: {
        id: number;
        name: string;
    };
    department: {
        id: number;
        name: string;
    };
    variationTypes: variationType[],
    variations: Array<{
        id: number;
        variation_type_option_ids: number[];
        quantity: number;
        price: number;  
    }> 
}

export type PaginationProp<T> = {
    data: Array<T>
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
};
