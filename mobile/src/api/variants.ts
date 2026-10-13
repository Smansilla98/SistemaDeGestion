import type { ProductRow, ProductVariant } from './types';

/** Una fila vendible: un producto sin variantes, o una variante concreta de un producto. */
export type SellableOption = {
  key: string;
  product: ProductRow;
  variant: ProductVariant | null;
  name: string;
  price: number;
};

/**
 * Un producto con variantes (ej. Pizza) no tiene precio propio: se vende eligiendo una.
 * Se listan como "Pizza · Napolitana", "Pizza · Jamón y morrones", cada una con su precio,
 * así el mozo la encuentra buscando el sabor y la carga con un toque.
 */
export function sellableOptions(products: ProductRow[]): SellableOption[] {
  return products.flatMap((product): SellableOption[] => {
    const variants = product.variants ?? [];
    if (variants.length === 0) {
      return [
        {
          key: String(product.id),
          product,
          variant: null,
          name: product.name,
          price: Number(product.price),
        },
      ];
    }
    return variants.map((variant) => ({
      key: `${product.id}:${variant.id}`,
      product,
      variant,
      name: `${product.name} · ${variant.name}`,
      price: Number(variant.price),
    }));
  });
}
