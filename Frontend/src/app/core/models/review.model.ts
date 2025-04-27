export interface Review {
  id?: number;
  transactionId: number;
  rating: number;
  comment?: string;
  reviewDate?: Date;
  reviewerType: 'buyer' | 'seller';
}
