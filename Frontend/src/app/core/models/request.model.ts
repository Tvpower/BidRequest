export interface Request {
  request_id: number;
  user_id: number;
  title: string;
  description: string;
  category_id: number;
  type: 'service' | 'product';
  budget?: number;
  desired_condition?: 'new' | 'like-new' | 'good' | 'fair' | 'poor' | 'any';
  creation_date: string;
  expiration_date: string;
  status: 'active' | 'in_progress' | 'completed' | 'closed' | 'expired';
  requester_name?: string;
  category_name?: string;
  specifications?: RequestSpecification[];
  bids_count?: number;
}

export interface RequestSpecification {
  detail_id?: number;
  specification_type: string;
  specification_value: string;
  is_required: boolean;
}

export interface RequestsResponse {
  requests: Request[];
  pagination: {
    total: number;
    page: number;
    limit: number;
    total_pages: number;
  };
}
