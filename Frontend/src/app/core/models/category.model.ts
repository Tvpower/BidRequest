export interface Category {
  category_id: number;
  name: string;
  description?: string;
}

export interface CategoriesResponse {
  categories: Category[];
}
