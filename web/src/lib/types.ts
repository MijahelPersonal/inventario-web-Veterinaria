export interface Product {
  id: number;
  name: string;
  description: string;
  code: string;
  category: string;
  brand: string;
  presentation: string;
  price: string;
  available: string;
  image: string | null;
  demo?: boolean;
}
export interface Service {
  id: number;
  name: string;
  description: string;
  price: string;
  duration_minutes: number;
}
export interface Veterinarian {
  id: number;
  name: string;
}
export interface Catalog {
  products: Product[];
  services: Service[];
  veterinarians: Veterinarian[];
}
