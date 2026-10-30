<?php
declare(strict_types=1);
namespace App\Service;

// Un único cálculo para el escaparate y el pedido: nunca acepta importes del navegador.
final class WebPricing
{
    public static function apply(array $product, ?string $today = null): array
    {
        $today ??= (new \DateTimeImmutable('now', new \DateTimeZone('America/Lima')))->format('Y-m-d');
        $regular = (string) $product['price'];
        $offer = $product['offer_price'] ?? null;
        $active = $offer !== null && bccomp((string) $offer, '0', 2) > 0
            && bccomp((string) $offer, $regular, 2) < 0
            && (empty($product['offer_from']) || $product['offer_from'] <= $today)
            && (empty($product['offer_until']) || $product['offer_until'] >= $today);
        $product['regular_price'] = $regular;
        $product['price'] = $active ? (string) $offer : $regular;
        $product['on_sale'] = $active;
        $product['discount_percent'] = $active ? (int) bcdiv(bcmul(bcsub($regular, (string) $offer, 2), '100', 4), $regular, 0) : 0;
        unset($product['offer_price'], $product['offer_from'], $product['offer_until']);
        return $product;
    }
}
