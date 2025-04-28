export interface Bid {
  bid_id: number;
  request_id: number;
  seller_id: number;
  price: number;
  proposal: string;
  delivery_time: string;
  submission_date: string;
  status: 'pending' | 'accepted' | 'rejected';
  seller_name?: string;
  company_name?: string;
  seller_rating?: number;
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
