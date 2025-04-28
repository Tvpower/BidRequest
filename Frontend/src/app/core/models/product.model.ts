export interface Product {
  product_id: number;
  user_id: number;
  title: string;
  description: string;
  category_id: number;
  price: number;
  condition: 'new' | 'like-new' | 'good' | 'fair' | 'poor';
  creation_date: string;
  status: 'available' | 'sold' | 'reserved' | 'removed';
  seller_name?: string;
  category_name?: string;
  images?: ProductImage[];
  specifications?: ProductSpecification[];
}

export interface ProductImage {
  image_id: number;
  product_id: number;
  image_url: string;
  is_primary: boolean;
}

export interface ProductSpecification {
  detail_id?: number;
  specification_type: string;
  specification_value: string;
}

export interface ProductsResponse {
  products: Product[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}
