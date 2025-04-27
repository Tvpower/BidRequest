export interface User {
  id?: number;
  username: string;
  email: string;
  userType: 'buyer' | 'seller' | 'admin';
  registrationDate: Date;
  accountStatus: 'active' | 'inactive' | 'suspended';
}

export interface Seller {
  id?: number;
  userId: number
  companyName: string;
  contactInfo: string;
  rating: number;
  verificationStatus: 'verified' | 'unverified' | 'pending';
}
