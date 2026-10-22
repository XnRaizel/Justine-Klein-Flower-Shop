(function () {
	const root = document.getElementById('site-app') || document.getElementById('admin-app');
	if (!root || typeof Vue === 'undefined') {
		return;
	}

	const productsNode = document.getElementById('shop-products');
	const products = productsNode ? JSON.parse(productsNode.textContent) : [];
	const shopNode = document.getElementById('shop-vue');

	Vue.createApp({
		data() {
			return {
				menuOpen: false,
				sidebarOpen: false,
				products: products,
				query: '',
				inStockOnly: false,
				productUrl: shopNode ? shopNode.dataset.productUrl : ''
			};
		},
		computed: {
			visibleProducts() {
				const query = this.query.toLocaleLowerCase();
				return this.products.filter((product) => {
					const matchesQuery = product.name.toLocaleLowerCase().includes(query)
						|| product.category_name.toLocaleLowerCase().includes(query);
					return matchesQuery && (!this.inStockOnly || product.stock_qty > 0);
				});
			}
		},
		methods: {
			handleSubmit(event) {
				const form = event.target;
				if (!(form instanceof HTMLFormElement) || !form.checkValidity()) {
					return;
				}

				const submitter = event.submitter;
				if (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement) {
					submitter.disabled = true;
				}
			}
		}
	}).mount(root);

	const fallback = document.getElementById('shop-fallback');
	if (fallback) {
		fallback.hidden = true;
	}
})();
