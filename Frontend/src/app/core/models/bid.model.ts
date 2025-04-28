export interface Bid {
  bid_id: number;
  request_id: number;
  seller_id: number;
  price: number;
  description: string;
  product_condition?: 'new' | 'like-new' | 'good' | 'fair' | 'poor';
  product_brand?: string;
  product_model?: string;
  delivery_time?: string;
  submission_date: string;
  status: 'active' | 'accepted' | 'rejected' | 'withdrawn' | 'sold' | 'shipped' | 'delivered';
  seller_name?: string;
  company_name?: string;
  seller_rating?: number;
  images?: BidImage[];
}

export interface BidImage {
  image_id: number;
  bid_id: number;
  image_url: string;
  is_primary: boolean;
  upload_date: string;
}

export interface BidsResponse {
  bids: Bid[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}
