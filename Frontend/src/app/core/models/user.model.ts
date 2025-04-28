export interface User {
  user_id: number;
  username: string;
  email: string;
  user_type: 'buyer' | 'seller';
  registration_date?: string;
}

export interface AuthResponse {
  token: string;
  user: User;
}
