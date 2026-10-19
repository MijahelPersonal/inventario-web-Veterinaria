// El carrito calcula una vista previa; PHP vuelve a validar precios y existencias al confirmar.
export function mountPointOfSale() {
    const root = document.getElementById('point-of-sale');
    if (!root) return;
    const storageKey = `struch-pos-${root.dataset.user}`;
    const form = document.getElementById('checkout-form');
    const lines = document.getElementById('cart-lines');
    const inputs = document.getElementById('cart-inputs');
    const client = document.getElementById('pos-client');
    const requestKey = document.getElementById('cart-request-key');
    const button = document.getElementById('checkout-button');
    const error = document.getElementById('cart-error');
    let cart = new Map();
    let busy = false;

    const money = (cents) =>
        new Intl.NumberFormat('es-PE', {
            style: 'currency',
            currency: root.dataset.currency || 'PEN',
        }).format(Number(cents) / 100);
    const scaled = (text, scale) => {
        if (!/^\d{1,9}(\.\d{1,3})?$/.test(String(text))) return null;
        const [whole, fraction = ''] = String(text).split('.');
        if (fraction.length > scale) return null;
        return BigInt(whole + fraction.padEnd(scale, '0'));
    };
    const make = (tag, className, text) => {
        const node = document.createElement(tag);
        node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const save = () => {
        try {
            sessionStorage.setItem(
                storageKey,
                JSON.stringify({
                    items: [...cart.values()],
                    client: client.value,
                    key: requestKey.value,
                }),
            );
        } catch {}
    };

    if (root.dataset.confirmed) {
        try {
            sessionStorage.removeItem(storageKey);
        } catch {}
    } else {
        try {
            const saved = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
            if (saved && Array.isArray(saved.items) && saved.items.length <= 50) {
                for (const item of saved.items) {
                    if (
                        /^\d+$/.test(String(item.id)) &&
                        typeof item.name === 'string' &&
                        scaled(item.price, 2) !== null &&
                        scaled(item.quantity, 3) !== null &&
                        scaled(item.stock, 3) !== null
                    )
                        cart.set(String(item.id), item);
                }
                if (/^[a-f0-9]{64}$/.test(saved.key)) requestKey.value = saved.key;
                client.value = saved.client || '';
            }
        } catch {}
    }

    const render = () => {
        lines.replaceChildren();
        inputs.replaceChildren();
        let total = 0n;
        let invalid = false;
        let index = 0;
        for (const [id, item] of cart) {
            const quantity = scaled(item.quantity, 3);
            const price = scaled(item.price, 2);
            const stock = scaled(item.stock, 3);
            const valid = quantity !== null && quantity > 0n && quantity <= stock;
            invalid ||= !valid;
            const row = make('div', 'cart-line');
            row.append(make('strong', '', item.name));
            const controls = make('div', 'cart-line-controls');
            const field = make('input', 'form-control form-control-sm');
            field.type = 'number';
            field.min = '0.001';
            field.step = '0.001';
            field.max = item.stock;
            field.value = item.quantity;
            field.setAttribute('aria-label', `Cantidad de ${item.name}`);
            field.addEventListener('change', () => {
                item.quantity = field.value;
                render();
            });
            const remove = make('button', 'remove-line', 'Quitar');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Quitar ${item.name}`);
            remove.addEventListener('click', () => {
                cart.delete(id);
                render();
            });
            const subtotal = valid ? (price * quantity + 500n) / 1000n : 0n;
            total += subtotal;
            controls.append(field, make('span', '', money(subtotal)), remove);
            row.append(controls);
            if (!valid)
                row.append(
                    make(
                        'small',
                        'text-danger',
                        `Disponible al agregar: ${item.stock}. Revisa la cantidad.`,
                    ),
                );
            lines.append(row);
            for (const [key, value] of Object.entries({
                item: `product:${id}`,
                quantity: item.quantity,
            })) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `items[${index}][${key}]`;
                hidden.value = value;
                inputs.append(hidden);
            }
            index++;
        }
        document.getElementById('cart-empty').hidden = cart.size > 0;
        document.getElementById('cart-count').textContent =
            `${cart.size} ${cart.size === 1 ? 'producto' : 'productos'}`;
        document.getElementById('cart-subtotal').textContent = money(total);
        document.getElementById('cart-total').textContent = money(total);
        error.textContent = invalid ? 'Revisa las cantidades antes de continuar.' : '';
        button.disabled = !cart.size || invalid || busy;
        save();
    };

    root.querySelectorAll('.add-product').forEach((add) => {
        add.addEventListener('click', () => {
            const { id, name, price, stock } = add.dataset;
            const old = cart.get(id);
            const quantity = old ? scaled(old.quantity, 3) : 0n;
            const available = scaled(stock, 3);
            const next = (quantity ?? 0n) + 1000n;
            if (next > available) {
                error.textContent =
                    'No hay otra unidad completa disponible. Puedes ajustar una cantidad fraccionada en el resumen.';
                if (!old && available > 0n)
                    cart.set(id, { id, name, price, stock, quantity: String(stock) });
                render();
                return;
            }
            if (!old && cart.size >= 50) {
                error.textContent = 'El carrito admite hasta 50 productos distintos.';
                return;
            }
            cart.set(id, {
                id,
                name,
                price,
                stock,
                quantity: `${next / 1000n}.${String(next % 1000n).padStart(3, '0')}`,
            });
            render();
        });
    });
    client.addEventListener('change', save);
    document.getElementById('clear-cart').addEventListener('click', () => {
        cart.clear();
        render();
    });
    form.addEventListener('submit', (event) => {
        if (button.disabled || busy) {
            event.preventDefault();
            return;
        }
        busy = true;
        button.disabled = true;
        button.textContent = 'Confirmando…';
        save();
    });
    // Al volver desde un error de validación se recupera el carrito sin reenviar el formulario.
    window.addEventListener('pageshow', () => {
        busy = false;
        button.textContent = 'Finalizar venta';
        render();
    });
    render();
}
