(() => {
  const CART_KEY = "suaveurban_cart_clean_v1";
  const CUSTOMER_KEY = "suaveurban_customer_clean_v1";
  const UID_KEY = "suaveurban_customer_uid_v1";
  const ENDPOINT = '/api/web/crear_orden.php';
  const CART_API = '/api/web/carrito.php';
  const isLoggedClient = () => document.body && document.body.dataset.webClientLogged === '1';

  const page = document.querySelector('[data-cart-page]');
  if (!page) return;

  const read = (k, f) => { try { return JSON.parse(localStorage.getItem(k) || JSON.stringify(f)); } catch { return f; } };
  const write = (k, v) => localStorage.setItem(k, JSON.stringify(v));
  const money = (n) => '$' + Number(n || 0).toLocaleString('es-MX') + ' MXN';
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

  const getUid = () => {
    let uid = localStorage.getItem(UID_KEY);
    if (!uid) { uid = 'web_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10); localStorage.setItem(UID_KEY, uid); }
    return uid;
  };

  let serverItems = null;
  const cart = () => (isLoggedClient() && Array.isArray(serverItems)) ? serverItems : read(CART_KEY, []);
  const customer = () => read(CUSTOMER_KEY, {});
  const setCustomer = (c) => write(CUSTOMER_KEY, c || {});

  function updateBadge() {
    const count = cart().reduce((t, i) => t + Number(i.qty || 1), 0);
    document.querySelectorAll('[data-cart-count]').forEach((el) => { el.textContent = count; });
  }

  async function setCart(next) {
    if (isLoggedClient()) {
      serverItems = Array.isArray(next) ? next : [];
    } else {
      write(CART_KEY, next);
    }
    updateBadge();
    render();
  }

  async function syncServerCart() {
    if (!isLoggedClient()) return;
    try {
      const res = await fetch(CART_API, { credentials: 'same-origin' });
      const j = await res.json();
      if (j && j.ok && Array.isArray(j.items)) serverItems = j.items;
    } catch (_) {}
  }

  function render() {
    const items = cart();
    if (!items.length) {
      page.innerHTML = `<div class="empty-state cart-empty"><h2>Tu carrito está vacío.</h2><p>Agrega productos desde colecciones para preparar tu compra.</p><a class="btn btn--gold" href="/colecciones">Ver colecciones</a></div>`;
      return;
    }
    const c = customer();
    const total = items.reduce((s, i) => s + Number(i.price || 0) * Number(i.qty || 1), 0);

    page.innerHTML = `<div class="cart-layout"><div class="cart-main"><div class="cart-toolbar"><b>${items.reduce((t, i) => t + Number(i.qty || 1), 0)} productos</b><button type="button" class="cart-link-danger" data-cart-clear>Vaciar carrito</button></div><div class="cart-list">${items.map((item, index) => `<article class="cart-item cart-item--complete"><a class="cart-item__media" href="${esc(item.url || '/producto/' + item.id)}">${item.image ? `<img src="${esc(item.image)}" alt="${esc(item.name)}">` : 'SU'}</a><div class="cart-item__info"><h3><a href="${esc(item.url || '/producto/' + item.id)}">${esc(item.name || 'Producto')}</a></h3><p>${[item.size ? `Talla: ${esc(item.size)}` : '', item.color ? `Color: ${esc(item.color)}` : ''].filter(Boolean).join(' · ') || 'Sin variantes seleccionadas'}</p><strong>${money(item.price)}</strong></div><div class="qty cart-item__qty"><button type="button" data-cart-minus="${index}">−</button><input type="number" min="1" max="99" value="${Number(item.qty || 1)}" data-cart-qty="${index}"><button type="button" data-cart-plus="${index}">+</button></div><div class="cart-item__total"><b>${money(Number(item.price || 0) * Number(item.qty || 1))}</b></div><button type="button" class="remove" data-cart-remove="${index}">Eliminar</button></article>`).join('')}</div><a class="btn btn--ghost" href="/colecciones">Continuar comprando</a></div><aside class="cart-summary"><h2>Resumen</h2><div class="cart-summary__line"><span>Subtotal</span><b>${money(total)}</b></div><div class="cart-summary__line"><span>Envío</span><b>Por confirmar</b></div><div class="cart-summary__total"><span>Total estimado</span><strong>${money(total)}</strong></div><p class="cart-note">Finaliza tu compra para registrarla en el sistema.</p><div class="cart-customer"><h3>Datos para preparar pedido</h3><label>Nombre<input type="text" data-customer-field="nombre" value="${esc(c.nombre || '')}" placeholder="Nombre completo"></label><label>Teléfono<input type="tel" data-customer-field="telefono" value="${esc(c.telefono || '')}" placeholder="WhatsApp o teléfono"></label><label>Correo<input type="email" data-customer-field="correo" value="${esc(c.correo || '')}" placeholder="correo@ejemplo.com"></label><label>Dirección / referencia<textarea data-customer-field="direccion" placeholder="Dirección o referencia de entrega">${esc(c.direccion || '')}</textarea></label><label>Notas<textarea data-customer-field="notas" placeholder="Notas del pedido">${esc(c.notas || '')}</textarea></label></div><button type="button" class="btn btn--gold btn--wide" data-create-web-order>Finalizar compra</button></aside></div>`;
  }

  async function submitOrder() {
    const items = cart();
    const c = customer();
    if (!String(c.nombre || '').trim()) return alert('Captura tu nombre.');
    if (!String(c.telefono || '').trim()) return alert('Captura tu teléfono.');
    const payload = { origen: 'web_publica', cliente: { nombre: c.nombre || '', telefono: c.telefono || '', correo: c.correo || '', direccion: c.direccion || '', notas: c.notas || '', web_cliente_uid: getUid() }, carrito: items.map((i) => ({ id: i.id, name: i.name, qty: i.qty, price: i.price, size: i.size, color: i.color, image: i.image, url: i.url })) };

    console.log('Checkout endpoint:', ENDPOINT);
    console.log('Checkout payload:', payload);

    const res = await fetch('/api/web/crear_orden.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
    const text = await res.text();
    console.log('Checkout response status:', res.status);
    console.log('Checkout response text:', text);

    let data = {};
    try { data = text ? JSON.parse(text) : {}; } catch (_) {}
    if (!res.ok || !data.ok) throw new Error((data && data.mensaje) || text || 'No se pudo crear la orden');

    localStorage.removeItem(CART_KEY);
    updateBadge();
    render();
    alert(`Tu pedido fue recibido correctamente. Folio: ${((data.orden || {}).folio || 'N/A')}`);
  }

  document.addEventListener('click', async (ev) => {
    const plus = ev.target.closest('[data-cart-plus]');
    const minus = ev.target.closest('[data-cart-minus]');
    const remove = ev.target.closest('[data-cart-remove]');
    const clear = ev.target.closest('[data-cart-clear]');
    const create = ev.target.closest('[data-create-web-order]');

    if (plus || minus || remove) {
      const arr = cart();
      const idx = Number((plus || minus || remove).dataset.cartPlus ?? (plus || minus || remove).dataset.cartMinus ?? (plus || minus || remove).dataset.cartRemove);
      if (!Number.isInteger(idx) || !arr[idx]) return;
      if (isLoggedClient()) {
        const row = arr[idx] || {};
        if (remove) {
          fetch(CART_API, { method:'DELETE', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ item_id: row.item_id || 0, product_id: row.id || 0 }) }).then(r=>r.json()).then(j=>{ if(j&&j.ok&&Array.isArray(j.items)){ serverItems=j.items; updateBadge(); render(); }});
        } else {
          const qty = plus ? Math.min(99, Number(row.qty || 1) + 1) : Math.max(1, Number(row.qty || 1) - 1);
          fetch(CART_API, { method:'PATCH', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ product_id: row.id || 0, qty, size: row.size || '', color: row.color || '' }) }).then(r=>r.json()).then(j=>{ if(j&&j.ok&&Array.isArray(j.items)){ serverItems=j.items; updateBadge(); render(); }});
        }
        return;
      }
      if (remove) arr.splice(idx, 1);
      else if (plus) arr[idx].qty = Math.min(99, Number(arr[idx].qty || 1) + 1);
      else arr[idx].qty = Math.max(1, Number(arr[idx].qty || 1) - 1);
      return setCart(arr);
    }
    if (clear) { if (isLoggedClient()) { fetch(CART_API,{method:'DELETE',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({clear:1})}).then(r=>r.json()).then(j=>{ if(j&&j.ok){ serverItems=[]; updateBadge(); render(); }}); return; } return setCart([]); }
    if (create) {
      try { await submitOrder(); }
      catch (e) { alert('Error al crear orden: ' + (e.message || 'Error desconocido')); }
    }
  });

  document.addEventListener('input', (ev) => {
    const qty = ev.target.closest('[data-cart-qty]');
    if (qty) {
      const arr = cart(); const idx = Number(qty.dataset.cartQty);
      if (arr[idx]) { const newQty=Math.max(1, Math.min(99, Number(qty.value || 1))); if (isLoggedClient()) { const row=arr[idx]; fetch(CART_API,{method:'PATCH',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({product_id: row.id || 0, qty:newQty, size: row.size || '', color: row.color || ''})}).then(r=>r.json()).then(j=>{ if(j&&j.ok&&Array.isArray(j.items)){ serverItems=j.items; updateBadge(); render(); }}); } else { arr[idx].qty = newQty; setCart(arr); } }
      return;
    }
    const field = ev.target.closest('[data-customer-field]');
    if (field) {
      const c = customer(); c[field.dataset.customerField] = field.value; setCustomer(c);
    }
  });

  syncServerCart().finally(() => { updateBadge(); render(); });
})();
