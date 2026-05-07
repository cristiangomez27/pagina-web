(() => {
  if (window.SUAVE_CART_CHECKOUT_ONLY === true && document.querySelector("[data-cart-page]")) return;
  const CART_KEY = "suaveurban_cart_clean_v1";
  const FAV_KEY = "suaveurban_favs_clean_v1";
  const CUSTOMER_KEY = "suaveurban_customer_clean_v1";
  const WEB_CUSTOMER_UID_KEY = "suaveurban_customer_uid_v1";
  const WEB_ORDER_ENDPOINT = "/ventas/api/web/crear_orden.php";
  const WEB_FAV_ENDPOINT = "/api/web/favoritos.php";

  const $all = (s, root = document) => Array.from(root.querySelectorAll(s));
  const $ = (s, root = document) => root.querySelector(s);
  const money = (n) => "$" + Number(n || 0).toLocaleString("es-MX") + " MXN";
  const read = (key, fallback = []) => {
    try { return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback)); } catch { return fallback; }
  };
  const write = (key, value) => localStorage.setItem(key, JSON.stringify(value));
  const getCart = () => read(CART_KEY, []);
  const getCustomer = () => read(CUSTOMER_KEY, {});
  const setCustomer = (data) => write(CUSTOMER_KEY, data || {});
  const getFavs = () => read(FAV_KEY, []);
  const getCustomerUid = () => {
    let uid = localStorage.getItem(WEB_CUSTOMER_UID_KEY);
    if (!uid) {
      uid = "web_" + Date.now().toString(36) + "_" + Math.random().toString(36).slice(2, 10);
      localStorage.setItem(WEB_CUSTOMER_UID_KEY, uid);
    }
    return uid;
  };
  const setFavs = (favs) => { write(FAV_KEY, favs); updateBadges(); renderFavoritesPage(); };
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, m => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[m]));

  function toast(message, type = "ok") {
    let el = document.querySelector(".su-toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "su-toast";
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.dataset.type = type;
    el.classList.add("is-visible");
    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(() => el.classList.remove("is-visible"), 2600);
  }

  function setCart(cart) {
    const clean = (Array.isArray(cart) ? cart : [])
      .map(item => ({
        id: String(item.id || ""),
        name: String(item.name || "Producto"),
        price: Number(item.price || 0),
        image: String(item.image || ""),
        url: String(item.url || (item.id ? `/producto/${item.id}` : "/colecciones")),
        size: String(item.size || ""),
        color: String(item.color || ""),
        qty: Math.max(1, Math.min(99, Number(item.qty || 1)))
      }))
      .filter(item => item.id !== "");
    write(CART_KEY, clean);
    updateBadges();
    renderCartPage();
  }

  function cartCount() {
    return getCart().reduce((total, item) => total + Number(item.qty || 1), 0);
  }

  function updateBadges() {
    $all("[data-cart-count]").forEach(el => el.textContent = cartCount());
    $all("[data-fav-count]").forEach(el => el.textContent = getFavs().length);
  }

  function currentOption(selector) {
    const active = document.querySelector(selector + ".is-active");
    return active ? active.textContent.trim() : "";
  }

  function productQty() {
    const input = document.querySelector("[data-product-qty]");
    if (!input) return 1;
    const value = Math.max(1, Math.min(99, Number(input.value || 1)));
    input.value = value;
    return value;
  }

  function markOptionError(type, message) {
    const block = document.querySelector(`[data-required-option="${type}"]`);
    if (block) {
      block.classList.add("has-error");
      block.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    toast(message, "error");
  }

  function buildCartItem(button) {
    if (button.disabled) return null;
    const id = String(button.dataset.id || "");
    if (!id) return null;
    const size = currentOption("[data-size-option]");
    const color = currentOption("[data-color-option]");
    document.querySelectorAll(".option-block.has-error").forEach(el => el.classList.remove("has-error"));
    if (button.dataset.requiresSize === "1" && !size) {
      markOptionError("size", "Elige una talla antes de agregar.");
      return null;
    }
    if (button.dataset.requiresColor === "1" && !color) {
      markOptionError("color", "Elige un color antes de agregar.");
      return null;
    }
    return {
      id,
      name: button.dataset.name || "Producto",
      price: Number(button.dataset.price || 0),
      image: button.dataset.image || "",
      url: button.dataset.url || `/producto/${id}`,
      size,
      color,
      qty: productQty()
    };
  }

  function addCart(button, redirect = false) {
    const item = buildCartItem(button);
    if (!item) return false;
    const cart = getCart();
    const found = cart.find(x => String(x.id) === item.id && String(x.size || "") === item.size && String(x.color || "") === item.color);
    if (found) found.qty = Math.min(99, Number(found.qty || 1) + item.qty);
    else cart.push(item);
    setCart(cart);
    if (redirect) {
      window.location.href = "/carrito";
      return true;
    }
    button.classList.add("is-added");
    const old = button.textContent;
    button.textContent = "Agregado ✓";
    toast("Producto agregado al carrito.");
    setTimeout(() => { button.classList.remove("is-added"); button.textContent = old; }, 1200);
    return true;
  }

  async function toggleFav(data) {
    const server = await toggleFavServer(data);
    if (server && server.ok) {
      toast(server.removed ? "Eliminado de favoritos." : "Agregado a favoritos.");
      return;
    }
    const favs = getFavs();
    const idx = favs.findIndex(x => String(x.id) === String(data.id));
    if (idx >= 0) {
      favs.splice(idx, 1);
      toast("Eliminado de favoritos.");
    } else {
      favs.push(data);
      toast("Agregado a favoritos.");
    }
    setFavs(favs);
  }

  function summaryText(cart, total, customer = {}) {
    const lines = ["Pedido Suave Urban Studio", ""];
    cart.forEach((item, i) => {
      lines.push(`${i + 1}. ${item.name}`);
      if (item.size) lines.push(`   Talla: ${item.size}`);
      if (item.color) lines.push(`   Color: ${item.color}`);
      lines.push(`   Cantidad: ${item.qty || 1}`);
      lines.push(`   Precio: ${money(item.price)}`);
      lines.push(`   Subtotal: ${money(Number(item.price || 0) * Number(item.qty || 1))}`);
    });
    lines.push("");
    lines.push(`Total estimado: ${money(total)}`);
    if (customer.nombre || customer.telefono || customer.direccion) {
      lines.push("");
      lines.push("Datos del cliente:");
      if (customer.nombre) lines.push(`Nombre: ${customer.nombre}`);
      if (customer.telefono) lines.push(`Teléfono: ${customer.telefono}`);
      if (customer.correo) lines.push(`Correo: ${customer.correo}`);
      if (customer.direccion) lines.push(`Dirección: ${customer.direccion}`);
      if (customer.notas) lines.push(`Notas: ${customer.notas}`);
    }
    return lines.join("\n");
  }

  function waUrl(phone, text) {
    phone = String(phone || "").trim();
    if (!phone) return "#";
    if (/^https?:\/\//i.test(phone)) return phone + (phone.includes("?") ? "&" : "?") + "text=" + encodeURIComponent(text);
    return "https://wa.me/" + phone.replace(/\D+/g, "") + "?text=" + encodeURIComponent(text);
  }

  async function submitWebOrder() {
    const cart = getCart();
    const customer = getCustomer();
    if (!cart.length) {
      toast("Tu carrito está vacío.", "error");
      return;
    }
    if (!String(customer.nombre || "").trim()) {
      toast("Captura tu nombre para crear la orden.", "error");
      const field = document.querySelector('[data-customer-field="nombre"]');
      if (field) field.focus();
      return;
    }
    if (!String(customer.telefono || "").trim()) {
      toast("Captura tu WhatsApp para crear la orden.", "error");
      const field = document.querySelector('[data-customer-field="telefono"]');
      if (field) field.focus();
      return;
    }

    const total = cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 1), 0);
    const button = document.querySelector("[data-create-web-order]");
    const oldText = button ? button.textContent : "";
    if (button) {
      button.disabled = true;
      button.textContent = "Creando orden...";
    }

    const currentPage = window.location.pathname || "";
    const endpoint = "/ventas/api/web/crear_orden.php";
    const payload = {
      origen: "web_publica",
      cliente: {
        nombre: customer.nombre || "",
        telefono: customer.telefono || "",
        correo: customer.correo || "",
        direccion: customer.direccion || "",
        notas: customer.notas || "",
        web_cliente_uid: getCustomerUid()
      },
      carrito: cart,
      total
    };
    console.log("Checkout endpoint:", endpoint, "| page:", currentPage);
    console.log("Checkout payload:", payload);

    try {
      const response = await fetch('/ventas/api/web/crear_orden.php', {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const rawText = await response.text();
      console.log("Checkout response status:", response.status);
      console.log("Checkout response text:", rawText);
      let data = {};
      try {
        data = rawText ? JSON.parse(rawText) : {};
      } catch (_) {
        data = {};
      }
      if (!response.ok || !data.ok) {
        const serverMsg = data.mensaje || rawText || response.statusText || "No se pudo crear la orden web.";
        throw new Error(`HTTP ${response.status} en ${endpoint}: ${serverMsg}`);
      }
      localStorage.setItem("suaveurban_last_web_order", JSON.stringify(data.orden || data));
      toast("Compra registrada. Falta confirmar el pago para procesarla.");
      const code = data.orden && data.orden.codigo ? data.orden.codigo : "orden web";
      alert("Compra registrada: " + code + "\n\nQuedó pendiente de pago. En el sistema interno aparecerá como pedido web pendiente.");
    } catch (err) {
      const errMsg = (err && err.message) ? err.message : "Error desconocido al crear orden web.";
      const finalMsg = `Error al crear orden.\nURL: ${endpoint}\nDetalle: ${errMsg}`;
      toast(finalMsg, "error");
      alert(finalMsg);
    } finally {
      if (button) {
        button.disabled = false;
        button.textContent = oldText || "Finalizar compra";
      }
    }
  }


  function renderCartPage() {
    const page = document.querySelector("[data-cart-page]");
    if (!page) return;
    const cart = getCart();
    if (!cart.length) {
      page.innerHTML = `
        <div class="empty-state cart-empty">
          <h2>Tu carrito está vacío.</h2>
          <p>Agrega productos desde colecciones para preparar tu compra.</p>
          <a class="btn btn--gold" href="/colecciones">Ver colecciones</a>
        </div>`;
      return;
    }
    const total = cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 1), 0);
    const customer = getCustomer();
    const checkoutText = summaryText(cart, total, customer);
    const checkout = waUrl(page.dataset.whatsapp, checkoutText);

    page.innerHTML = `
      <div class="cart-layout">
        <div class="cart-main">
          <div class="cart-toolbar">
            <b>${cartCount()} producto${cartCount() === 1 ? "" : "s"}</b>
            <button type="button" class="cart-link-danger" data-cart-clear>Vaciar carrito</button>
          </div>
          <div class="cart-list">
            ${cart.map((item, index) => {
              const subtotal = Number(item.price || 0) * Number(item.qty || 1);
              const variant = [item.size ? `Talla: ${esc(item.size)}` : "", item.color ? `Color: ${esc(item.color)}` : ""].filter(Boolean).join(" · ");
              return `
              <article class="cart-item cart-item--complete">
                <a class="cart-item__media" href="${esc(item.url || `/producto/${item.id}`)}">
                  ${item.image ? `<img src="${esc(item.image)}" alt="${esc(item.name)}">` : "SU"}
                </a>
                <div class="cart-item__info">
                  <h3><a href="${esc(item.url || `/producto/${item.id}`)}">${esc(item.name)}</a></h3>
                  ${variant ? `<p>${variant}</p>` : `<p>Sin variantes seleccionadas</p>`}
                  <strong>${money(item.price)}</strong>
                </div>
                <div class="qty cart-item__qty">
                  <button type="button" data-cart-minus="${index}" aria-label="Disminuir">−</button>
                  <input type="number" min="1" max="99" value="${Number(item.qty || 1)}" data-cart-qty="${index}" aria-label="Cantidad">
                  <button type="button" data-cart-plus="${index}" aria-label="Aumentar">+</button>
                </div>
                <div class="cart-item__total"><b>${money(subtotal)}</b></div>
                <button type="button" class="remove" data-cart-remove="${index}">Eliminar</button>
              </article>`;
            }).join("")}
          </div>
          <a class="btn btn--ghost" href="/colecciones">Continuar comprando</a>
        </div>

        <aside class="cart-summary">
          <h2>Resumen</h2>
          <div class="cart-summary__line"><span>Subtotal</span><b>${money(total)}</b></div>
          <div class="cart-summary__line"><span>Envío</span><b>Por confirmar</b></div>
          <div class="cart-summary__total"><span>Total estimado</span><strong>${money(total)}</strong></div>
          <p class="cart-note">Finaliza tu compra para registrarla en el sistema. El pedido se procesa cuando el pago quede confirmado.</p>

          <div class="cart-customer">
            <h3>Datos para preparar pedido</h3>
            <label>Nombre<input type="text" data-customer-field="nombre" value="${esc(customer.nombre || "")}" placeholder="Nombre completo"></label>
            <label>Teléfono<input type="tel" data-customer-field="telefono" value="${esc(customer.telefono || "")}" placeholder="WhatsApp o teléfono"></label>
            <label>Correo<input type="email" data-customer-field="correo" value="${esc(customer.correo || "")}" placeholder="correo@ejemplo.com"></label>
            <label>Dirección / referencia<textarea data-customer-field="direccion" placeholder="Dirección o referencia de entrega">${esc(customer.direccion || "")}</textarea></label>
            <label>Notas<textarea data-customer-field="notas" placeholder="Notas del pedido">${esc(customer.notas || "")}</textarea></label>
          </div>

          <button type="button" class="btn btn--gold btn--wide" data-create-web-order>Finalizar compra</button>
        </aside>
      </div>`;
  }

  function renderFavoritesPage() {
    const page = document.querySelector("[data-favorites-page]");
    if (!page) return;
    const favs = getFavs();
    if (!favs.length) {
      page.innerHTML = `<div class="empty-state">Aún no tienes favoritos.</div><a class="btn btn--gold" href="/colecciones">Ver colecciones</a>`;
      return;
    }
    page.innerHTML = `<section class="product-grid">${favs.map(item => `
      <article class="product-card">
        <a class="product-card__media" href="${esc(item.url || `/producto/${item.id}`)}">
          ${item.image ? `<img src="${esc(item.image)}" alt="${esc(item.name)}">` : `<span class="image-placeholder">SU</span>`}
        </a>
        <div class="product-card__body">
          <h3><a href="${esc(item.url || `/producto/${item.id}`)}">${esc(item.name)}</a></h3>
          <strong>${money(item.price)}</strong>
          <div class="product-card__actions">
            <a href="${esc(item.url || `/producto/${item.id}`)}">Ver producto</a>
            <button type="button" data-remove-fav="${esc(item.id)}">Quitar</button>
          </div>
        </div>
      </article>
    `).join("")}</section>`;
  }

  document.addEventListener("click", async (ev) => {
    const menu = ev.target.closest("[data-menu-toggle]");
    if (menu) document.body.classList.toggle("menu-open");

    const add = ev.target.closest("[data-add-cart]");
    if (add) addCart(add, false);

    const buyNow = ev.target.closest("[data-buy-now]");
    if (buyNow) addCart(buyNow, true);

    const option = ev.target.closest("[data-size-option], [data-color-option]");
    if (option) {
      const group = option.hasAttribute("data-size-option") ? "[data-size-option]" : "[data-color-option]";
      $all(group).forEach(x => x.classList.remove("is-active"));
      option.classList.add("is-active");
      const block = option.closest(".option-block");
      if (block) block.classList.remove("has-error");
    }

    const qtyPlus = ev.target.closest("[data-product-qty-plus]");
    const qtyMinus = ev.target.closest("[data-product-qty-minus]");
    if (qtyPlus || qtyMinus) {
      const input = $("[data-product-qty]");
      if (input) {
        const current = Number(input.value || 1);
        input.value = Math.max(1, Math.min(99, current + (qtyPlus ? 1 : -1)));
      }
    }

    const thumb = ev.target.closest("[data-thumb]");
    if (thumb) {
      const main = document.querySelector("[data-main-product-image]");
      if (main) main.src = thumb.dataset.thumb;
      $all("[data-thumb]").forEach(x => x.classList.remove("is-active"));
      thumb.classList.add("is-active");
    }

    const share = ev.target.closest("[data-share-product]");
    if (share) {
      const text = share.dataset.shareText || document.title;
      const url = window.location.href;
      if (navigator.share) {
        try { await navigator.share({ title: document.title, text, url }); }
        catch (_) {}
      } else {
        try {
          await navigator.clipboard.writeText(`${text}
${url}`);
          toast("Link del producto copiado.");
        } catch {
          toast("No se pudo copiar el link.", "error");
        }
      }
    }

    const plus = ev.target.closest("[data-cart-plus]");
    const minus = ev.target.closest("[data-cart-minus]");
    const remove = ev.target.closest("[data-cart-remove]");
    if (plus || minus || remove) {
      const cart = getCart();
      const index = Number((plus || minus || remove).dataset.cartPlus ?? (plus || minus || remove).dataset.cartMinus ?? (plus || minus || remove).dataset.cartRemove);
      if (!Number.isInteger(index) || !cart[index]) return;
      if (remove) {
        cart.splice(index, 1);
        toast("Producto eliminado.");
      } else if (plus) cart[index].qty = Math.min(99, Number(cart[index].qty || 1) + 1);
      else if (minus) cart[index].qty = Math.max(1, Number(cart[index].qty || 1) - 1);
      setCart(cart);
    }

    const clear = ev.target.closest("[data-cart-clear]");
    if (clear) {
      if (confirm("¿Vaciar todo el carrito?")) {
        setCart([]);
        toast("Carrito vacío.");
      }
    }

    const createWebOrder = ev.target.closest("[data-create-web-order]");
    if (createWebOrder) {
      console.log("[checkout] click detectado en Finalizar compra");
      await submitWebOrder();
    }

    const copy = ev.target.closest("[data-cart-copy]");
    if (copy) {
      const cart = getCart();
      const total = cart.reduce((sum, item) => sum + Number(item.price || 0) * Number(item.qty || 1), 0);
      const text = summaryText(cart, total, getCustomer());
      try {
        await navigator.clipboard.writeText(text);
        toast("Resumen copiado.");
      } catch {
        toast("No se pudo copiar automáticamente.", "error");
      }
    }

    const removeFav = ev.target.closest("[data-remove-fav]");
    if (removeFav) {
      const favId = String(removeFav.dataset.removeFav);
      if (isLoggedClient()) {
        try {
          const res = await fetch(WEB_FAV_ENDPOINT, { method: "DELETE", credentials: "same-origin", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ product_id: Number(favId || 0) }) });
          const json = await res.json();
          if (json && json.ok && Array.isArray(json.items)) write(FAV_KEY, json.items);
        } catch (_) {}
      } else {
        const favs = getFavs().filter(x => String(x.id) !== favId);
        write(FAV_KEY, favs);
      }
      updateBadges();
      renderFavoritesPage();
      toast("Favorito eliminado.");
    }
  });

  document.addEventListener("input", (ev) => {
    const qty = ev.target.closest("[data-cart-qty]");
    if (qty) {
      const cart = getCart();
      const index = Number(qty.dataset.cartQty);
      if (cart[index]) {
        cart[index].qty = Math.max(1, Math.min(99, Number(qty.value || 1)));
        setCart(cart);
      }
    }

    const field = ev.target.closest("[data-customer-field]");
    if (field) {
      const customer = getCustomer();
      customer[field.dataset.customerField] = field.value;
      setCustomer(customer);
    }
  });

  $all(".product-card").forEach(card => {
    if (card.querySelector(".fav-floating")) return;
    const media = card.querySelector(".product-card__media");
    const title = card.querySelector("h3 a");
    if (!media || !title) return;
    const id = card.dataset.productId || (title.getAttribute("href") || "").split("/").pop();
    const img = card.querySelector("img")?.getAttribute("src") || "";
    const priceText = card.querySelector("strong")?.textContent || "0";
    const price = Number(priceText.replace(/[^0-9.]/g, "")) || 0;
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "fav-floating";
    btn.innerHTML = "♡";
    btn.setAttribute("aria-label", "Agregar a favoritos");
    btn.addEventListener("click", async (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      await toggleFav({ id, name: title.textContent.trim(), price, image: img, url: title.getAttribute("href") || `/producto/${id}` });
    });
    media.appendChild(btn);
  });

  document.addEventListener("DOMContentLoaded", () => {
    const alert = document.getElementById("cwAutoAlert");
    if (alert) {
      setTimeout(() => {
        alert.classList.add("is-hiding");
        setTimeout(() => alert.remove(), 600);
      }, 4000);
    }
  });

  updateBadges();
  renderCartPage();
  renderFavoritesPage();
  syncFavsFromServer();
})();
