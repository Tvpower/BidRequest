export interface Transaction {
  transaction_id: number;
  request_id: number;
  bid_id: number;
  user_id: number;
  seller_id: number;
  amount: number;
  payment_status: 'pending' | 'paid' | 'refunded' | 'cancelled';
  payment_date?: string;
  shipping_address?: string;
  tracking_number?: string;
  delivery_status: 'not_applicable' | 'pending' | 'shipped' | 'delivered';
  request_title?: string;
  buyer_name?: string;
  seller_name?: string;
}

export interface TransactionsResponse {
  transactions: Transaction[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}
