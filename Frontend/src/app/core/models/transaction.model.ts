export interface Transaction {
  id?: number;
  requestId: number;
  bidId: number;
  userId: number;
  sellerId: number;
  amount: number;
  paymentStatus: 'pending' | 'completed' | 'refunded' | 'failed' ;
  paymentDate?: Date;
}
