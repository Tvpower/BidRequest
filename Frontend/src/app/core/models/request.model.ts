import {Bid} from './bid.model';

export interface Request {
  id?: number;
  userId: number;
  title: string;
  description: string;
  categoryId: number;
  createdAt?: Date;
  expiresAt?: Date;
  price: number;
  status: 'active' | 'expired' | 'completed' | 'cancelled';
  details?: RequestDetail[];
  bids?: Bid[]

}

export interface RequestDetail {
  id?: number;
  requestId: number;
  description: string;
}
