// Dynamic cart totals and stock check
function updateCartTotal() {
    let total = 0;
    document.querySelectorAll('.cart-item').forEach(item => {
        const qty = parseInt(item.querySelector('.cart-qty').value);
        const price = parseFloat(item.dataset.price);
        item.querySelector('.item-total').textContent = (qty * price).toFixed(2);
        total += qty * price;
    });
    document.getElementById('cart-total').textContent = total.toFixed(2);
}

async function checkStock(item) {
    const pid = item.dataset.productId;
    const qty = parseInt(item.querySelector('.cart-qty').value);
    if (!pid || qty < 1) return;

    try {
        const res = await fetch(`php/product_read.php?id=${pid}`);
        const data = await res.json();
        const stockElem = item.querySelector('.item-stock');
        if (data.stock < qty) {
            stockElem.textContent = `Only ${data.stock} in stock!`;
            stockElem.style.color = 'red';
        } else {
            stockElem.textContent = `In stock: ${data.stock}`;
            stockElem.style.color = 'green';
        }
    } catch(err) { console.error(err); }
}

document.querySelectorAll('.cart-item').forEach(item => {
    item.querySelector('.cart-qty').addEventListener('input', () => {
        checkStock(item);
        updateCartTotal();
    });
});
updateCartTotal();
