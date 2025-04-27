export interface Bid {
  id?: number;
  requestId: number;
  sellerId: number;
  price: number;
  description?: string;
  deliveryTime?: string;
  submissionDate?: Date;
  status: 'pending' | 'accepted' | 'rejected' | 'withdrawn';
}
