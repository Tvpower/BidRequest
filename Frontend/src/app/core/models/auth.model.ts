import {Seller, User} from './user.model';

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  username: string;
  email: string;
  password: string;
  userType: 'buyer' | 'seller';
  companyName?: string;
  contactInfo?: string;
}

export interface AuthResponse {
  token: string;
  user: User;
  seller?: Seller;
}
