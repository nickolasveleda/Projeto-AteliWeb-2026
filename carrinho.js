(() => {
  "use strict";

  const STORAGE_KEY = "cart";
  const CHECKOUT_URL = "compra.html";

  // Valida o preço 
  function isValidPrice(price) {
    if (typeof price !== "number" && typeof price !== "string") {
      return false;
    }

    if (typeof price === "string") {
      const normalizedPrice = price.trim();

      // Aceita apenas números decimais convencionais
      if (!/^\d+(\.\d+)?$/.test(normalizedPrice)) {
        return false;
      }

      price = normalizedPrice;
    }

    const parsedPrice = Number(price);

    return (Number.isFinite(parsedPrice) && parsedPrice >= 0);
  }


  // Carrega e valida os dados salvos no navegador
  function loadCart() {
    try {
      const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));

      if (!Array.isArray(saved)) return [];

      return saved
        .filter(item =>
          item &&
          Number.isSafeInteger(item.id) &&
          item.id > 0 &&
          typeof item.name === "string" &&
          item.name.trim() !== "" &&
          typeof item.price === "number" &&
          Number.isFinite(item.price) &&
          item.price >= 0 &&
          Number.isSafeInteger(item.quantity) &&
          item.quantity > 0 &&
          typeof item.image === "string"
        )
        .map(item => ({
          id: item.id,
          name: item.name,
          price: item.price,
          image: item.image,
          quantity: item.quantity
        }));
    } catch (error) {
      console.error("Erro ao carregar o carrinho: ", error);
      return [];
    }
  }

  let cart = loadCart();

  function getElement(id) {
    return document.getElementById(id);
  }

  // Salva o carrinho no localStorage
  function saveCart() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
    } catch (error) {
      console.error("Erro ao salvar o carrinho: ", error);
      alert("Não foi possível salvar o carrinho neste navegador");
    }
  }

  // Formata valores em reais
  function formatPrice(value) {
    return Number(value).toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL"
    });
  }

  // Abre o carrinho sem alternar seu estado
  function openCart() {
    getElement("cart")?.classList.add("toggle");
    getElement("overlay")?.classList.add("active");
  }

  // Fecha o carrinho
  function closeCart() {
    getElement("cart")?.classList.remove("toggle");
    getElement("overlay")?.classList.remove("active");
  }

  // Alterna entre abrir e fechar
  function toggleCart() {
    const cartElement = getElement("cart");

    if (!cartElement) return;

    if (cartElement.classList.contains("toggle")) {
      closeCart();
    } else {
      openCart();
    }
  }

  // Adiciona um produto ao carrinho
  function addToCart(id, name, price, image) {
    if (
      !Number.isSafeInteger(id) ||
      id <= 0 ||
      typeof name !== "string" ||
      !name.trim() ||
      typeof price !== "number" ||
      !Number.isFinite(price) ||
      price < 0 ||
      typeof image !== "string"
    ) {
      console.error("Produto inválido", { id, name, price, image });
      alert("Não foi possível adicionar este produto ao carrinho");
      return;
    }

    const existing = cart.find(item => item.id === id);

    if (existing) {
      if (existing.quantity >= Number.MAX_SAFE_INTEGER) {
        alert("Limite de quantidade atingido");
        return;
      }

      existing.quantity++;
    } else {
      cart.push({
        id,
        name,
        price,
        image,
        quantity: 1
      });
    }

    saveCart();
    renderCart();
    openCart();
  }

  // Aumenta a quantidade
  function increaseQty(index) {
    if (!cart[index]) return;

    if (!Number.isSafeInteger(cart[index].quantity)) return;
    if (cart[index].quantity >= 100) return;

    cart[index].quantity++;

    saveCart();
    renderCart();
  }


  // Diminui a quantidade ou remove o produto
  function decreaseQty(index) {
    if (!cart[index]) return;

    if (cart[index].quantity > 1) {
      cart[index].quantity--;
    } else {
      cart.splice(index, 1);
    }

    saveCart();
    renderCart();
  }

  // Remove um produto específico
  function removeItem(index) {
    if (!Number.isInteger(index) || index < 0 || index >= cart.length) {
      return;
    }

    cart.splice(index, 1);
    saveCart();
    renderCart();
  }

  // Limpa todos os produtos
  function clearCart() {
    if (cart.length === 0) return;

    if (!confirm("Deseja remover todos os produtos do carrinho?")) {
      return;
    }

    cart = [];
    saveCart();
    renderCart();
  }

  // Renderiza os produtos
  function renderCart() {
    const cartItems = getElement("cart-items");
    const totalElement = getElement("total");
    const checkoutButton = getElement("checkout-btn");

    if (!cartItems || !totalElement || !checkoutButton) return;

    cartItems.replaceChildren();

    let total = 0;

    cart.forEach((item, index) => {
      const subtotal = item.price * item.quantity;
      total += subtotal;

      const li = document.createElement("li");

      // Imagem do produto
      const image = document.createElement("img");
      image.className = "item-img";
      image.src = item.image;
      image.alt = item.name;
      image.loading = "lazy";

      // Oculta a imagem caso ela não seja carregada
      image.addEventListener("error", () => {
        image.hidden = true;
      });

      li.appendChild(image);

      // Nome do produto
      const info = document.createElement("div");
      info.className = "item-info";

      const name = document.createElement("span");
      name.className = "item-name";
      name.textContent = item.name;

      info.appendChild(name);
      li.appendChild(info);

      // Controles de quantidade
      const controls = document.createElement("div");
      controls.className = "item-controls";

      const decreaseButton = document.createElement("button");
      decreaseButton.type = "button";
      decreaseButton.textContent = "−";
      decreaseButton.setAttribute(
        "aria-label",
        `Diminuir quantidade de ${item.name}`
      );
      decreaseButton.addEventListener("click", () => {
        decreaseQty(index);
      });

      const quantity = document.createElement("span");
      quantity.textContent = String(item.quantity);

      const increaseButton = document.createElement("button");
      increaseButton.type = "button";
      increaseButton.textContent = "+";
      increaseButton.setAttribute(
        "aria-label",
        `Aumentar quantidade de ${item.name}`
      );
      increaseButton.addEventListener("click", () => {
        increaseQty(index);
      });

      controls.append(
        decreaseButton,
        quantity,
        increaseButton
      );

      li.appendChild(controls);

      // Subtotal do produto
      const subtotalElement = document.createElement("div");
      subtotalElement.className = "item-subtotal";
      subtotalElement.textContent = formatPrice(subtotal);
      li.appendChild(subtotalElement);

      // Botão de remoção
      const removeButton = document.createElement("button");
      removeButton.type = "button";
      removeButton.className = "remove-btn";
      removeButton.textContent = "✕";
      removeButton.setAttribute(
        "aria-label",
        `Remover ${item.name} do carrinho`
      );
      removeButton.addEventListener("click", () => {
        removeItem(index);
      });

      li.appendChild(removeButton);
      cartItems.appendChild(li);
    });

    // Atualiza o total e o botão de checkout
    totalElement.textContent = `Total: ${formatPrice(total)}`;

    checkoutButton.style.display =
      cart.length > 0 ? "block" : "none";

    // Atualiza o contador, caso exista no HTML
    const cartCount = getElement("cart-count");

    if (cartCount) {
      cartCount.textContent = String(
        cart.reduce((sum, item) => sum + item.quantity, 0)
      );
    }
  }

  // Redireciona para a finalização da compra
  function goToCheckout() {
    if (cart.length === 0) {
      alert("Seu carrinho está vazio.");
      return;
    }

    window.location.href = CHECKOUT_URL;
  }

  // Mantém compatibilidade com os onclick existentes no HTML
  Object.assign(window, {
    openCart,
    closeCart,
    toggleCart,
    addToCart,
    increaseQty,
    decreaseQty,
    removeItem,
    clearCart,
    renderCart,
    goToCheckout
  });

  // Sincroniza o carrinho se outra aba alterar o armazenamento
  window.addEventListener("storage", event => {
    if (event.key === STORAGE_KEY) {
      cart = loadCart();
      renderCart();
    }
  });

  // Renderiza o carrinho ao carregar a página
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderCart, { once: true });
  } else {
    renderCart();
  }
})();