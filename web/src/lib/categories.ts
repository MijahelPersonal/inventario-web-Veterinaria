import type { Product } from './types';
export const normalize = (value: string) =>
  value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
export const categories = [
  {
    slug: 'antiparasitarios',
    title: 'Antiparasitarios',
    description: 'Encuentra opciones y consulta al equipo antes de elegir.',
    image: '/images/farmacia.jpg',
    icon: 'shield',
  },
  {
    slug: 'perros',
    title: 'Perros',
    description: 'Todo para su vida de aventuras.',
    image: '/images/perro-v2.webp',
    icon: 'paw',
  },
  {
    slug: 'gatos',
    title: 'Gatos',
    description: 'Un mundo a la altura de su curiosidad.',
    image: '/images/gato-v2.webp',
    icon: 'heart',
  },
  {
    slug: 'farmacia',
    title: 'Farmacia veterinaria',
    description: 'Cuidados que empiezan con una buena orientación.',
    image: '/images/farmacia.jpg',
    icon: 'shield',
  },
  {
    slug: 'alimentos',
    title: 'Alimentos y nutrición',
    description: 'Bienestar que se sirve todos los días.',
    image: '/images/alimentacion.jpg',
    icon: 'bowl',
  },
  {
    slug: 'accesorios',
    title: 'Accesorios y cuidado',
    description: 'Sus rutinas, con un poco más de alegría.',
    image: '/images/accesorios.jpg',
    icon: 'bag',
  },
  {
    slug: 'ofertas',
    title: 'Ofertas y novedades',
    description: 'Descubre qué hay de nuevo para tu compañero.',
    image: '/images/companeros-v2.webp',
    icon: 'leaf',
  },
];
export function matchesCategory(product: Product, slug: string) {
  const text = normalize([product.name, product.description, product.category].join(' '));
  if (slug === 'antiparasitarios')
    return product.web_group === 'antiparasitarios' || /antiparas|antipulg|desparas/.test(text);
  if (['perros', 'gatos'].includes(slug) && product.pet_type && product.pet_type !== 'ambos')
    return product.pet_type === slug;
  if (['perros', 'gatos'].includes(slug) && product.pet_type === 'ambos') return true;
  if (
    ['farmacia', 'alimentos', 'accesorios'].includes(slug) &&
    product.web_group &&
    product.web_group !== 'general'
  )
    return (
      product.web_group === slug ||
      (slug === 'farmacia' && product.web_group === 'antiparasitarios')
    );
  if (slug === 'perros') return /perr|canin|cachorr/.test(text);
  if (slug === 'gatos') return /gat|felin/.test(text);
  if (slug === 'farmacia')
    return /farmac|medic|antiparas|antipulg|suplement|veterin|desparas/.test(text);
  if (slug === 'alimentos') return /aliment|nutri|snack|comida|croqueta/.test(text);
  if (slug === 'accesorios')
    return /accesori|juguet|higiene|cuidado|collar|correa|cama|arena|champ|shamp|cepillo/.test(
      text,
    );
  if (slug === 'ofertas') return product.on_sale === true;
  return true;
}
