import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
	BrowserRouter,
	Link,
	Navigate,
	Route,
	Routes,
	useLocation,
	useNavigate,
	useParams,
	useSearchParams
} from 'react-router-dom';
import './style.css';

const API = '/api/v1';

async function api(path, options = {}) {
	const { csrf, ...requestOptions } = options;
	const response = await fetch(`${API}${path}`, {
		credentials: 'same-origin',
		headers: {
			...(requestOptions.body ? { 'Content-Type': 'application/json' } : {}),
			...(csrf ? { 'X-CSRF-Token': csrf } : {}),
			...requestOptions.headers
		},
		...requestOptions
	});
	const payload = await response.json().catch(() => ({}));
	if (!response.ok) {
		const error = new Error(payload.error || 'The request could not be completed.');
		error.validation = payload.errors || {};
		throw error;
	}
	return payload;
}

function useSession() {
	const [session, setSession] = useState({ ready: false, user: null, csrf: '' });
	useEffect(() => {
		api('/auth/session')
			.then((result) => setSession({ ready: true, user: result.user, csrf: result.csrf }))
			.catch((error) => setSession({ ready: true, user: null, csrf: '', error: error.message }));
	}, []);
	return [session, setSession];
}

function Header({ user, onLogout }) {
	const [menuOpen, setMenuOpen] = useState(false);
	const { pathname } = useLocation();
	const navigation = [
		{ to: '/', label: 'Home', icon: 'home', active: pathname === '/' },
		{ to: '/shop', label: 'Shop flowers', icon: 'flower', active: pathname.startsWith('/shop') || pathname.startsWith('/product/') },
		{ to: '/custom-orders', label: 'Custom orders', icon: 'message', active: pathname.startsWith('/custom-orders') },
		{ to: '/track', label: 'Track an order', icon: 'package', active: pathname === '/track' },
		{ to: '/about', label: 'Our story', icon: 'heart', active: pathname === '/about' }
	];
	return (
		<>
			<button className="sidebar-toggle" type="button" onClick={() => setMenuOpen(!menuOpen)} aria-expanded={menuOpen} aria-controls="store-sidebar">
				<span aria-hidden="true">☰</span><span>Menu</span>
			</button>
			<button className={`sidebar-scrim${menuOpen ? ' visible' : ''}`} type="button" aria-label="Close navigation menu" onClick={() => setMenuOpen(false)} tabIndex={menuOpen ? 0 : -1} />
			<aside className={`store-sidebar${menuOpen ? ' open' : ''}`} id="store-sidebar" aria-label="Main navigation">
				<div className="sidebar-brand-row">
					<Link className="brand" to="/" onClick={() => setMenuOpen(false)}>
						<img className="brand-mark" src="/assets/images/flower-logo.svg" alt="" />
						<span>Justine Klein<small>FLOWER SHOP</small></span>
					</Link>
					<button className="sidebar-close" type="button" onClick={() => setMenuOpen(false)} aria-label="Close navigation menu">×</button>
				</div>
				<p className="sidebar-label">Discover</p>
				<nav className="sidebar-nav">
					{navigation.map((item) => <Link className={item.active ? 'active' : ''} to={item.to} key={item.to} onClick={() => setMenuOpen(false)} aria-current={item.active ? 'page' : undefined}><span className="sidebar-icon" aria-hidden="true"><NavigationIcon name={item.icon} /></span>{item.label}</Link>)}
				</nav>
				<div className="sidebar-divider" />
				<p className="sidebar-label">Your account</p>
				<nav className="sidebar-nav sidebar-account">
					<Link className={pathname === '/cart' ? 'active' : ''} to="/cart" onClick={() => setMenuOpen(false)} aria-current={pathname === '/cart' ? 'page' : undefined}><span className="sidebar-icon" aria-hidden="true"><NavigationIcon name="bag" /></span>Your cart</Link>
					{user ? <><Link className={pathname === '/account/orders' ? 'active' : ''} to="/account/orders" onClick={() => setMenuOpen(false)} aria-current={pathname === '/account/orders' ? 'page' : undefined}><span className="sidebar-icon" aria-hidden="true"><NavigationIcon name="receipt" /></span>My orders</Link><button className="sidebar-action" type="button" onClick={() => { setMenuOpen(false); onLogout(); }}><span className="sidebar-icon" aria-hidden="true"><NavigationIcon name="logout" /></span>Sign out</button></> : <><Link className={pathname === '/login' ? 'active' : ''} to="/login" onClick={() => setMenuOpen(false)} aria-current={pathname === '/login' ? 'page' : undefined}><span className="sidebar-icon" aria-hidden="true"><NavigationIcon name="user" /></span>Sign in</Link><Link className="sidebar-join" to="/register" onClick={() => setMenuOpen(false)}>Create an account <span aria-hidden="true">→</span></Link></>}
				</nav>
				<div className="sidebar-note"><span className="sidebar-note-mark" aria-hidden="true">✿</span><p>Fresh flowers, thoughtfully arranged for life's lovely moments.</p><Link to="/shop" onClick={() => setMenuOpen(false)}>Explore the collection <span aria-hidden="true">→</span></Link></div>
				<div className="sidebar-footer"><span className="sidebar-status-dot" />Made with care, every day</div>
			</aside>
		</>
	);
}

function NavigationIcon({ name }) {
	const paths = {
		home: <><path d="m3 10 9-7 9 7" /><path d="M5 9v11h14V9M9 20v-6h6v6" /></>,
		flower: <><path d="M12 12c-2.2-2.6-3.2-5.1-1.6-6.7 1.1-1.1 2.8-.5 3.1 1 .4 1.6-.5 3.3-1.5 5.7Z" /><path d="M12 12c2.6-2.2 5.1-3.2 6.7-1.6 1.1 1.1.5 2.8-1 3.1-1.6.4-3.3-.5-5.7-1.5Z" /><path d="M12 12c2.2 2.6 3.2 5.1 1.6 6.7-1.1 1.1-2.8.5-3.1-1-.4-1.6.5-3.3 1.5-5.7Z" /><path d="M12 12c-2.6 2.2-5.1 3.2-6.7 1.6-1.1-1.1-.5-2.8 1-3.1 1.6-.4 3.3.5 5.7 1.5Z" /><circle cx="12" cy="12" r="1.5" /><path d="M12 14v7m0-3c-1.4-1.6-2.7-1.9-4.2-1.3.8 1.7 2.1 2.1 4.2 1.3Zm0-1c1.2-1.4 2.5-1.8 4-1.4-.6 1.7-1.9 2.3-4 1.4Z" /></>,
		message: <><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H5l1.5-3A7.5 7.5 0 1 1 20 11.5Z" /><path d="M8.5 11.5h.01m3.49 0h.01m3.49 0h.01" /></>,
		package: <><path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="M3 8v9l9 5 9-5V8m-9 5v9m-4-17 9 5" /></>,
		heart: <path d="M20.8 8.8c0 5.4-8.8 11.2-8.8 11.2S3.2 14.2 3.2 8.8A4.3 4.3 0 0 1 12 6.5a4.3 4.3 0 0 1 8.8 2.3Z" />,
		bag: <><path d="M5 8h14l1 13H4L5 8Z" /><path d="M9 8a3 3 0 0 1 6 0" /></>,
		receipt: <><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" /><path d="M9 8h6m-6 4h6m-6 4h3" /></>,
		logout: <><path d="M10 17l5-5-5-5m5 5H3" /><path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7" /></>,
		user: <><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></>
	};
	return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">{paths[name] || null}</svg>;
}

function SiteLayout({ session, setSession, children }) {
	const navigate = useNavigate();
	async function logout() {
		try {
			const result = await api('/auth/logout', { method: 'POST', csrf: session.csrf });
			setSession({ ready: true, user: null, csrf: result.csrf });
			navigate('/');
		} catch (error) {
			window.alert(error.message);
		}
	}
	return <><Header user={session.user} onLogout={logout} /><div className="store-main"><div className="store-topbar"><span>Flowers for life's lovely moments</span></div><main>{children}</main><footer className="site-footer"><div className="shell footer-inner"><span><img className="footer-mark" src="/assets/images/flower-logo.svg" alt="" /> Justine Klein Flower Shop</span><span>Thoughtfully arranged, always.</span></div></footer></div></>;
}

function Notice({ error }) {
	return error ? <p className="notice error" role="alert">{error}</p> : null;
}

function StatusBadge({ status }) {
	const normalized = String(status || 'unknown').toLowerCase().replaceAll(' ', '_');
	const label = String(status || 'Unknown').replaceAll('_', ' ');
	return <span className={`status-pill status-${normalized}`}>{label}</span>;
}

function BouquetIllustration() {
	const flowers = [
		{ x: 150, y: 166, color: 'coral', petals: 7, scale: 1.05, delay: '' },
		{ x: 229, y: 126, color: 'pink', petals: 6, scale: .78, delay: 'delay-a' },
		{ x: 322, y: 143, color: 'gold', petals: 8, scale: .94, delay: 'delay-b' },
		{ x: 405, y: 204, color: 'lilac', petals: 6, scale: .84, delay: 'delay-c' },
		{ x: 110, y: 258, color: 'pink', petals: 5, scale: .55, delay: 'delay-b' }
	];
	return <svg className="bouquet-illustration" viewBox="0 0 560 570" role="img" aria-labelledby="bouquet-title bouquet-description">
		<title id="bouquet-title">A hand-arranged garden bouquet</title>
		<desc id="bouquet-description">Coral, pink, golden and lilac flowers with sage leaves in a blush ceramic vase.</desc>
		<defs>
			<linearGradient id="petal-coral" x1="0" y1="0" x2=".7" y2="1"><stop offset="0" stopColor="#F28C8B" /><stop offset=".56" stopColor="#D9626B" /><stop offset="1" stopColor="#B94F5C" /></linearGradient>
			<linearGradient id="petal-pink" x1=".15" y1="0" x2=".75" y2="1"><stop offset="0" stopColor="#FFD0C5" /><stop offset=".6" stopColor="#F2A7A0" /><stop offset="1" stopColor="#DB8585" /></linearGradient>
			<linearGradient id="petal-gold" x1=".2" y1="0" x2=".7" y2="1"><stop offset="0" stopColor="#FFE28A" /><stop offset=".62" stopColor="#F2C14E" /><stop offset="1" stopColor="#DFA53E" /></linearGradient>
			<linearGradient id="petal-lilac" x1=".1" y1="0" x2=".8" y2="1"><stop offset="0" stopColor="#D8C4E7" /><stop offset=".6" stopColor="#B79AD0" /><stop offset="1" stopColor="#9378B3" /></linearGradient>
			<linearGradient id="leaf-gradient" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stopColor="#BED0A9" /><stop offset="1" stopColor="#6F8F6B" /></linearGradient>
			<linearGradient id="vase-gradient" x1=".1" y1="0" x2=".9" y2="1"><stop offset="0" stopColor="#F6E3D9" /><stop offset=".48" stopColor="#E9C9BC" /><stop offset="1" stopColor="#D8B2A4" /></linearGradient>
			<radialGradient id="flower-center" cx=".38" cy=".28" r=".8"><stop offset="0" stopColor="#FFE9A6" /><stop offset=".42" stopColor="#C88443" /><stop offset="1" stopColor="#80503E" /></radialGradient>
			<filter id="vase-shadow" x="-.5" y="-.5" width="2" height="2"><feGaussianBlur stdDeviation="8" /></filter>
		</defs>
		<ellipse cx="284" cy="495" rx="101" ry="15" fill="#9C7163" opacity=".14" filter="url(#vase-shadow)" />
		<g fill="none" stroke="#6F8F6B" strokeLinecap="round">
			<path d="M276 390C255 326 207 244 151 151" strokeWidth="5" /><path d="M284 390C276 310 250 241 229 126" strokeWidth="4.6" />
			<path d="M290 390C300 314 320 231 322 143" strokeWidth="5" /><path d="M294 390C327 315 375 257 405 204" strokeWidth="4.7" />
			<path d="M277 391C233 336 174 294 110 258" strokeWidth="4" /><path d="M299 391C354 344 410 318 455 286" strokeWidth="4" />
			<path d="M287 390C287 322 286 264 283 207" strokeWidth="4" />
		</g>
		<g fill="url(#leaf-gradient)" stroke="#E8F0DB" strokeOpacity=".7" strokeWidth="1.3" strokeLinecap="round">
			<path d="M206 280C214 261 234 252 253 260 245 277 226 287 206 280Z" /><path d="m209 279 39-17" />
			<path d="M241 333C234 317 220 310 204 314 211 330 225 338 241 333Z" /><path d="m238 332-28-15" />
			<path d="M270 309C280 290 300 283 318 292 308 308 290 316 270 309Z" /><path d="m273 308 39-14" />
			<path d="M320 272C329 252 350 245 368 255 357 271 339 280 320 272Z" /><path d="m323 271 38-13" />
			<path d="M333 332C326 314 311 306 294 310 302 328 315 337 333 332Z" /><path d="m330 331-29-16" />
			<path d="M366 282C375 263 395 256 413 266 403 282 384 290 366 282Z" /><path d="m369 281 39-13" />
			<path d="M188 320C180 304 165 298 149 303 157 319 172 325 188 320Z" /><path d="m185 319-29-14" />
			<path d="M396 339C405 321 425 315 443 325 433 341 414 348 396 339Z" /><path d="m399 338 39-13" />
		</g>
		<g className="bouquet-bud sway-a"><path d="M177 194C164 184 157 170 161 157" fill="none" stroke="#6F8F6B" strokeWidth="3.5" strokeLinecap="round" /><path d="M151 160C142 145 154 132 168 138 179 143 174 158 164 164Z" fill="url(#petal-pink)" /><path d="M155 155 165 142" fill="none" stroke="#FFF4E8" strokeWidth="1.5" strokeLinecap="round" /></g>
		<g className="bouquet-bud sway-b"><path d="M398 248C423 235 441 215 446 198" fill="none" stroke="#6F8F6B" strokeWidth="3.5" strokeLinecap="round" /><path d="M438 201C429 188 438 177 451 181 463 185 461 199 450 204Z" fill="url(#petal-gold)" /></g>
		{flowers.map((flower) => <g className={`bouquet-flower ${flower.delay}`} transform={`translate(${flower.x} ${flower.y}) rotate(${flower.x % 24 - 12}) scale(${flower.scale})`} key={`${flower.color}-${flower.x}`}>
			{Array.from({ length: flower.petals }, (_, index) => <ellipse key={index} cx="0" cy="-27" rx="8" ry="23" fill={`url(#petal-${flower.color})`} transform={`rotate(${index * (360 / flower.petals)})`} />)}
			{Array.from({ length: flower.petals }, (_, index) => <path key={index} d="M0-18c-2-7-2-14 0-20" fill="none" stroke="#FFF8E9" strokeOpacity=".52" strokeWidth="1.2" strokeLinecap="round" transform={`rotate(${index * (360 / flower.petals)})`} />)}
			<circle r="10" fill="url(#flower-center)" /><circle cx="-3" cy="-3" r="2" fill="#FFF0B9" opacity=".88" />
			<g fill="#F9D79A" opacity=".85"><circle cx="4" cy="-4" r="1.15" /><circle cx="5" cy="2" r=".9" /><circle cx="-5" cy="3" r="1" /></g>
		</g>)}
		<g className="bouquet-sparkle" fill="#E9B760"><path d="m457 147 5.2 10.6 11.7 1.7-8.45 8.2 2 11.6-10.45-5.5-10.45 5.5 2-11.6-8.45-8.2 11.7-1.7Z" /></g>
		<g className="bouquet-sparkle sparkle-b" fill="#FFFDF5"><path d="m96 221 3.4 7 7.7 1.1-5.55 5.4 1.3 7.6-6.85-3.6-6.85 3.6 1.3-7.6-5.55-5.4 7.7-1.1Z" /></g>
		<circle className="bouquet-dot drift-a" cx="83" cy="295" r="4.2" fill="#E3A19A" /><circle className="bouquet-dot" cx="477" cy="263" r="5" fill="#D98A87" />
		<circle className="bouquet-dot drift-b" cx="103" cy="191" r="3.4" fill="#E8B45D" /><circle className="bouquet-dot" cx="461" cy="330" r="3.7" fill="#B79AD0" />
		<ellipse cx="281" cy="383" rx="82" ry="6" fill="#9C7163" opacity=".12" />
		<path d="M197 377C214 389 348 389 365 377L351 476C349 494 337 505 319 507H243C225 505 213 494 211 476Z" fill="url(#vase-gradient)" stroke="#CDA99A" strokeWidth="2.2" />
		<path d="M209 391C212 426 218 461 222 479 224 490 232 496 246 498" fill="none" stroke="#FFF8F0" strokeWidth="5" strokeLinecap="round" opacity=".82" />
		<path d="M313 402C326 414 332 430 330 445 329 459 325 471 320 480" fill="none" stroke="#F8E9DF" strokeWidth="3" strokeLinecap="round" opacity=".86" />
		<path d="M197 375C214 383 348 383 365 375L362 390C342 399 221 399 200 390Z" fill="#E9CDBF" stroke="#CDA99A" strokeWidth="2" />
		<path d="M204 381C235 386 327 386 358 381" fill="none" stroke="#FFF8F1" strokeWidth="2.3" strokeLinecap="round" opacity=".85" />
	</svg>;
}

function HomePage() {
	const [catalog, setCatalog] = useState(null);
	const [error, setError] = useState('');
	useEffect(() => {
		api('/catalog').then(setCatalog).catch((failure) => {
			setError(failure.message);
			setCatalog({ categories: [], products: [] });
		});
	}, []);
	return <>
		<section className="hero"><div className="shell hero-grid"><div className="hero-copy"><span className="eyebrow">Flowers for life's lovely moments</span><h1>Make their day<br /><em>feel like a garden.</em></h1><p>Handpicked bouquets, made with care and delivered for the moments you want to remember.</p><Link className="button hero-cta" to="/shop">Explore the collection <span aria-hidden="true">→</span></Link><div className="hero-note"><span aria-hidden="true">✿</span> Thoughtfully arranged, always.</div></div><div className="hero-art"><div className="hero-art-stage" aria-hidden="true" /><div className="hero-art-ring" aria-hidden="true" /><BouquetIllustration /></div></div></section>
		<section className="section shell"><Notice error={error} /><div className="section-head"><div><span className="eyebrow">Find your moment</span><h2>Flowers for every occasion</h2></div><Link className="arrow-link" to="/shop">See all flowers →</Link></div><div className="occasion-grid">{(catalog?.categories || []).map((item) => <Link className="occasion-card" to={`/shop/occasion/${item.slug}`} key={item.category_id}><span className="occasion-icon">{emojiFor(item.slug)}</span><b>{item.name}</b><span>Explore flowers →</span></Link>)}</div></section>
		<section className="section featured"><div className="shell"><div className="section-head"><div><span className="eyebrow">Picked with love</span><h2>Customer favorites</h2></div><Link className="arrow-link" to="/shop">Shop all flowers →</Link></div><ProductGrid products={(catalog?.products || []).slice(0, 3)} /></div></section>
	</>;
}

function emojiFor(slug = '') {
	return ({ birthday: '🎂', anniversary: '💍', graduation: '🎓', sympathy: '🕊️', 'thank-you': '💐' })[slug] || '🌷';
}

function ProductGrid({ products }) {
	if (!products?.length) return <p className="empty-state">No bouquets to show yet.</p>;
	return <div className="product-grid">{products.map((product) => <article className="product-card" key={product.product_id}><Link to={`/product/${product.product_id}`} className="product-art">{emojiFor(product.category_slug)}</Link><div className="product-info"><span className="eyebrow">{product.category_name}</span><h3><Link to={`/product/${product.product_id}`}>{product.name}</Link></h3><div className="product-row"><b>₱{Number(product.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</b><Link className="round-link" to={`/product/${product.product_id}`} aria-label={`View ${product.name}`}>↗</Link></div></div></article>)}</div>;
}

function ShopPage() {
	const [catalog, setCatalog] = useState({ categories: [], products: [] });
	const [error, setError] = useState('');
	const [query, setQuery] = useState('');
	const [inStock, setInStock] = useState(false);
	const { slug } = useParams();
	useEffect(() => { api('/catalog').then(setCatalog).catch((failure) => setError(failure.message)); }, []);
	const visible = catalog.products.filter((item) => (!slug || item.category_slug === slug)
		&& (!inStock || Number(item.stock_qty) > 0)
		&& `${item.name} ${item.category_name}`.toLocaleLowerCase().includes(query.toLocaleLowerCase()));
	return <section className="section shell"><span className="eyebrow">Made fresh for you</span><h1 className="page-title">The flower collection</h1><Notice error={error} /><div className="filter-row"><div className="chips"><Link className={!slug ? 'chip active' : 'chip'} to="/shop">All flowers</Link>{catalog.categories.map((category) => <Link className={slug === category.slug ? 'chip active' : 'chip'} to={`/shop/occasion/${category.slug}`} key={category.category_id}>{category.name}</Link>)}</div><div className="filter-controls"><input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search bouquets..." aria-label="Search bouquets" /><label><input type="checkbox" checked={inStock} onChange={(event) => setInStock(event.target.checked)} /> In stock</label></div></div><p className="muted">{visible.length} bouquet(s)</p><ProductGrid products={visible} /></section>;
}

function ProductPage({ session }) {
	const { id } = useParams();
	const [product, setProduct] = useState(null);
	const [addons, setAddons] = useState({});
	const [selected, setSelected] = useState([]);
	const [error, setError] = useState('');
	useEffect(() => { api(`/products/${id}`).then((result) => { setProduct(result.product); setAddons(result.addons || {}); }).catch((failure) => setError(failure.message)); }, [id]);
	async function addToCart() {
		if (!session.user) return window.location.assign('/login');
		try {
			await api('/cart/items', { method: 'POST', csrf: session.csrf, body: JSON.stringify({ product_id: Number(id), quantity: 1, addon_ids: selected }) });
			window.location.assign('/cart');
		} catch (failure) { setError(failure.message); }
	}
	if (error) return <section className="section shell"><Notice error={error} /><Link className="arrow-link" to="/shop">Back to shop</Link></section>;
	if (!product) return <section className="section shell"><p>Loading bouquet…</p></section>;
	return <section className="section shell"><Link className="arrow-link" to="/shop">← Back to flowers</Link><div className="product-detail"><div className="product-art detail-art">{emojiFor(product.category_slug)}</div><div><span className="eyebrow">{product.category_name}</span><h1>{product.name}</h1><p className="muted">{product.description}</p><h2 className="detail-price">₱{Number(product.price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</h2><p className="muted">{Number(product.stock_qty) > 0 ? `${product.stock_qty} available` : 'Currently out of stock'}</p><Notice error={error} />{Object.entries(addons).map(([type, items]) => <fieldset className="addon-group" key={type}><legend>{type}</legend>{items.map((addon) => <label key={addon.addon_id}><input type="checkbox" checked={selected.includes(Number(addon.addon_id))} onChange={(event) => setSelected(event.target.checked ? [...selected, Number(addon.addon_id)] : selected.filter((addonId) => addonId !== Number(addon.addon_id)))} /> {addon.name}{Number(addon.price) ? ` (+₱${Number(addon.price).toFixed(2)})` : ''}</label>)}</fieldset>)}<button className="button" type="button" disabled={!Number(product.stock_qty)} onClick={addToCart}>Add to cart</button></div></div></section>;
}

function AuthPage({ mode, session, setSession }) {
	const navigate = useNavigate();
	const register = mode === 'register';
	const [form, setForm] = useState({ full_name: '', email: '', phone: '', password: '' });
	const [error, setError] = useState('');
	const [busy, setBusy] = useState(false);
	function update(event) { setForm({ ...form, [event.target.name]: event.target.value }); }
	async function submit(event) {
		event.preventDefault();
		setError('');
		setBusy(true);
		try {
			const result = await api(`/auth/${register ? 'register' : 'login'}`, { method: 'POST', csrf: session.csrf, body: JSON.stringify(form) });
			setSession({ ready: true, user: result.user, csrf: result.csrf });
			navigate(result.user.role === 'admin' ? '/admin' : result.user.role === 'rider' ? '/rider' : result.user.role === 'staff' ? '/staff' : '/');
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	return <section className="auth-section"><form className="auth-card" onSubmit={submit}><span className="eyebrow">{register ? 'A little more joy' : 'Welcome back'}</span><h1>{register ? 'Create your account' : 'Sign in'}</h1><p className="muted">{register ? 'Save your details and keep your order moments together.' : 'Sign in to continue to your flower shop.'}</p><Notice error={error} />{register && <label>Full name<input required name="full_name" autoComplete="name" value={form.full_name} onChange={update} /></label>}<label>Email address<input required type="email" name="email" autoComplete="email" value={form.email} onChange={update} /></label>{register && <label>Phone (optional)<input name="phone" autoComplete="tel" value={form.phone} onChange={update} /></label>}<label>Password<input required type="password" name="password" minLength={8} autoComplete={register ? 'new-password' : 'current-password'} value={form.password} onChange={update} /></label><button className="button full" disabled={busy || !session.ready}>{busy ? 'Please wait…' : register ? 'Create account' : 'Sign in'}</button><p className="muted auth-switch">{register ? 'Already have an account?' : 'New to the shop?'} <Link to={register ? '/login' : '/register'}>{register ? 'Sign in' : 'Create an account'}</Link></p></form></section>;
}

function AccountOrdersPage({ session }) {
	const [searchParams] = useSearchParams();
	const [orders, setOrders] = useState([]);
	const [error, setError] = useState('');
	const [showHidden, setShowHidden] = useState(false);
	useEffect(() => {
		if (!session.user) return;
		api('/orders').then((result) => setOrders(result.orders)).catch((failure) => setError(failure.message));
	}, [session.user]);
	async function cancel(orderId) {
		setError('');
		try {
			const result = await api(`/orders/${orderId}/cancel`, { method: 'POST', csrf: session.csrf });
			setOrders((current) => current.map((order) => order.order_id === orderId ? { ...order, status: result.order.status } : order));
		} catch (failure) { setError(failure.message); }
	}
	async function setHidden(order, hidden) {
		setError('');
		try {
			const result = await api(`/orders/${order.order_id}/visibility`, { method: 'PATCH', csrf: session.csrf, body: JSON.stringify({ hidden }) });
			setOrders((current) => current.map((item) => item.order_id === order.order_id ? { ...item, customer_hidden_at: result.hidden_at } : item));
		} catch (failure) { setError(failure.message); }
	}
	const hiddenCount = orders.filter((order) => Boolean(order.customer_hidden_at)).length;
	const visibleOrders = orders.filter((order) => Boolean(order.customer_hidden_at) === showHidden);
	return <section className="section shell"><span className="eyebrow">Your flower moments</span><h1 className="page-title">My orders</h1>{searchParams.get('payment') === 'processing' && <p className="notice payment-notice">Thank you. We are confirming your deposit with the payment provider. This page will show the order status once confirmation arrives.</p>}<Notice error={error} />{!session.user ? <p>Please <Link to="/login">sign in</Link> to view your orders.</p> : orders.length ? <>
		<div className="record-list-controls"><button className="text-button" type="button" disabled={!showHidden && !hiddenCount} onClick={() => setShowHidden((current) => !current)}>{showHidden ? 'Show active orders' : `Show hidden orders (${hiddenCount})`}</button></div>
		{visibleOrders.length ? <div className="table-wrap"><table><thead><tr><th>Order</th><th>Type</th><th>Date</th><th>Total</th><th>Deposit</th><th>Status</th><th>Actions</th></tr></thead><tbody>{visibleOrders.map((order) => {
		const canCancel = ['pending', 'confirmed', 'preparing'].includes(order.status)
			&& !['out_for_delivery', 'delivered'].includes(order.delivery_status);
		const onlinePaymentPending = order.payment_method === 'online' && order.deposit_session_exists && order.deposit_status !== 'paid';
		const refundAmount = Number(order.deposit_refunded || 0);
		return <tr key={order.order_id}><td>{order.order_number}</td><td>{order.custom_request_number ? `Custom: ${order.custom_order_title}` : 'Catalog'}</td><td>{String(order.created_at).slice(0, 10)}</td><td>₱{Number(order.total).toFixed(2)}</td><td>₱{Number(order.deposit_amount || 0).toFixed(2)} · {order.deposit_status || '—'}{refundAmount > 0 && <small className="refund-summary">₱{refundAmount.toFixed(2)} refunded</small>}</td><td><StatusBadge status={order.status} />{order.delivered_at && <small className="delivery-confirmation-details">Received by {order.delivered_to}; confirmed by {order.confirmed_by_name || 'Rider'} on {new Date(order.delivered_at.replace(' ', 'T')).toLocaleString()}</small>}{order.delivery_pin && <small className="delivery-pin">Recipient PIN: <b>{order.delivery_pin}</b><br />Share it with the rider only at handoff.</small>}</td><td><Link to={`/track?order=${encodeURIComponent(order.order_number)}`}>Track</Link>{order.payment_method === 'online' && order.deposit_status !== 'paid' && order.status === 'pending' && <button className="text-button cancel-order" type="button" onClick={async () => { try { const result = await api(`/orders/${order.order_id}/deposit`, { method: 'POST', csrf: session.csrf }); window.location.assign(result.payment_url); } catch (failure) { setError(failure.message); } }}>Pay deposit</button>}{canCancel && !onlinePaymentPending && <button className="text-button cancel-order" type="button" onClick={() => { if (window.confirm(`Cancel order ${order.order_number}? Any paid deposit refund must be arranged with the shop.`)) cancel(Number(order.order_id)); }}>Cancel order</button>}{['delivered', 'cancelled'].includes(order.status) && <button className="text-button cancel-order" type="button" onClick={() => setHidden(order, !showHidden)}>{showHidden ? 'Restore' : 'Hide'}</button>}</td></tr>;
	})}</tbody></table></div> : <p className="empty-state">{showHidden ? 'You have no hidden orders.' : 'Your order list is waiting for its first bouquet.'}</p>}
	</> : <p className="empty-state">Your order list is waiting for its first bouquet.</p>}</section>;
}

function TrackPage({ session }) {
	const [search] = useSearchParams();
	const initial = search.get('order') || '';
	const [number, setNumber] = useState(initial);
	const [email, setEmail] = useState('');
	const [order, setOrder] = useState(null);
	const [error, setError] = useState('');
	async function submit(event) { event.preventDefault(); setError(''); setOrder(null); try { const result = await api('/orders/track', { method: 'POST', csrf: session.csrf, body: JSON.stringify({ number, email }) }); setOrder(result.order); } catch (failure) { setError(failure.message); } }
	return <section className="section shell narrow"><span className="eyebrow">From our shop to their door</span><h1 className="page-title">Track an order</h1><form className="track-form" onSubmit={submit}><label>Order number<input value={number} onChange={(event) => setNumber(event.target.value)} placeholder="Enter order number" required /></label><label>Email used for the order<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></label><button className="button">Find my order</button></form><Notice error={error} />{order && <article className="order-card"><span className="eyebrow">{order.order_number}</span><h2><StatusBadge status={order.status} /></h2><p>{order.scheduled_at ? `Scheduled for ${order.scheduled_at}` : 'Delivery schedule will be updated soon.'}</p>{order.delivered_at && <p className="delivery-confirmation-details">Received by {order.delivered_to}; confirmed by {order.confirmed_by_name || 'Rider'} on {new Date(order.delivered_at.replace(' ', 'T')).toLocaleString()}</p>}{order.history?.length > 0 && <ol className="order-history">{order.history.map((entry, index) => <li key={`${entry.to_status}-${entry.changed_at}-${index}`}><StatusBadge status={entry.to_status} /><time>{new Date(entry.changed_at.replace(' ', 'T')).toLocaleString()}</time></li>)}</ol>}</article>}</section>;
}

function CartPage({ session }) {
	const [cart, setCart] = useState({ items: [], total: 0 });
	const [error, setError] = useState('');
	const [loading, setLoading] = useState(true);
	useEffect(() => {
		if (!session.user) { setLoading(false); return; }
		api('/cart').then(setCart).catch((failure) => setError(failure.message)).finally(() => setLoading(false));
	}, [session.user]);
	async function update(itemId, quantity) {
		if (!Number.isInteger(quantity) || quantity < 1) return;
		setError('');
		try { setCart(await api(`/cart/items/${itemId}`, { method: 'PATCH', csrf: session.csrf, body: JSON.stringify({ quantity }) })); }
		catch (failure) { setError(failure.message); }
	}
	async function remove(itemId) {
		setError('');
		try { setCart(await api(`/cart/items/${itemId}`, { method: 'DELETE', csrf: session.csrf })); }
		catch (failure) { setError(failure.message); }
	}
	if (!session.user) return <section className="section shell narrow"><h1 className="page-title">Your cart</h1><p className="muted">Sign in to save bouquets in your cart.</p><Link className="button" to="/login">Sign in</Link></section>;
	return <section className="section shell"><span className="eyebrow">A little something lovely</span><h1 className="page-title">Your cart</h1><Notice error={error} />{loading ? <p>Loading your cart…</p> : cart.items.length ? <><div className="cart-list">{cart.items.map((item) => <article className="cart-item" key={item.cart_item_id}><div className="product-art cart-art">{emojiFor('')}</div><div className="cart-item-info"><h2>{item.name}</h2><p className="muted">{item.addons.map((addon) => addon.name).join(', ') || 'No extras selected'}</p><b>₱{Number(item.unit_price).toFixed(2)} each</b></div><label className="quantity-control">Qty <input type="number" min="1" max={Math.min(99, item.stock_qty)} value={item.quantity} onChange={(event) => update(item.cart_item_id, Number(event.target.value))} /></label><strong>₱{Number(item.line_total).toFixed(2)}</strong><button className="text-button remove-button" type="button" onClick={() => remove(item.cart_item_id)}>Remove</button></article>)}</div><div className="cart-summary"><span>Subtotal</span><strong>₱{Number(cart.total).toFixed(2)}</strong><p className="muted">Delivery is currently free while Maps distance pricing is pending.</p><Link className="button" to="/checkout">Continue to checkout</Link></div></> : <div className="empty-state"><p>Your cart is waiting for its first bouquet.</p><Link className="button" to="/shop">Explore flowers</Link></div>}</section>;
}

function defaultDeliveryTime() {
	const date = new Date(Date.now() + 24 * 60 * 60 * 1000);
	date.setHours(10, 0, 0, 0);
	return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

function CheckoutPage({ session }) {
	const [searchParams] = useSearchParams();
	const [cart, setCart] = useState({ items: [], total: 0 });
	const [form, setForm] = useState({ recipient_name: session.user?.full_name || '', address: '', scheduled_at: defaultDeliveryTime(), card_message: '', payment_method: 'cash_at_shop' });
	const [error, setError] = useState('');
	const [order, setOrder] = useState(null);
	const [loading, setLoading] = useState(true);
	const [busy, setBusy] = useState(false);
	const [paymentOptions, setPaymentOptions] = useState({ deposit_percent: 50, online_available: false });
	const depositAmount = Number((Number(cart.total) * paymentOptions.deposit_percent / 100).toFixed(2));
	useEffect(() => {
		if (!session.user) { setLoading(false); return; }
		api('/cart').then(setCart).catch((failure) => setError(failure.message)).finally(() => setLoading(false));
	}, [session.user]);
	useEffect(() => {
		api('/payment-options').then(setPaymentOptions).catch((failure) => setError(failure.message));
	}, []);
	useEffect(() => {
		const orderNumber = searchParams.get('order');
		if (!session.user || !orderNumber) return;
		api('/orders').then((result) => {
			const found = (result.orders || []).find((item) => item.order_number === orderNumber);
			if (found) setOrder({
				...found,
				payment_method: found.payment_method === 'online' ? 'paymongo' : 'cash_at_shop',
				payment_returned: searchParams.get('payment') === 'processing',
			});
			else setError('We could not find that order in your account.');
		}).catch((failure) => setError(failure.message));
	}, [searchParams, session.user]);
	function update(event) { setForm({ ...form, [event.target.name]: event.target.value }); }
	async function submit(event) {
		event.preventDefault();
		setError('');
		setBusy(true);
		try {
			const result = await api('/orders', { method: 'POST', csrf: session.csrf, body: JSON.stringify(form) });
			if (result.order.payment_url) {
				window.location.assign(result.order.payment_url);
				return;
			}
			setOrder(result.order);
			setCart({ items: [], total: 0 });
			if (result.order.payment_error) setError(result.order.payment_error);
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	async function retryOnlinePayment() {
		setBusy(true);
		setError('');
		try {
			const result = await api(`/orders/${order.order_id}/deposit`, { method: 'POST', csrf: session.csrf });
			window.location.assign(result.payment_url);
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	if (!session.user) return <section className="section shell narrow"><h1 className="page-title">Checkout</h1><p>Please sign in before placing an order.</p><Link className="button" to="/login">Sign in</Link></section>;
	if (order) return <section className="section shell narrow"><span className="eyebrow">Order placed</span><h1 className="page-title">{order.payment_returned ? 'We are confirming your deposit.' : 'Thank you for choosing us.'}</h1><Notice error={error} /><p>Your order number is <strong>{order.order_number}</strong>.</p><p className="muted">Your required {paymentOptions.deposit_percent}% deposit is ₱{Number(order.deposit_amount).toFixed(2)}. {order.payment_method === 'paymongo' ? 'Complete your deposit on PayMongo before we confirm and prepare your flowers.' : 'Please pay your deposit at the shop before preparation. The remaining balance is due in cash on delivery.'}</p>{order.payment_method === 'paymongo' && !order.payment_url && !order.payment_returned && <button className="button" type="button" disabled={busy} onClick={retryOnlinePayment}>{busy ? 'Preparing secure checkout…' : 'Pay deposit online'}</button>}<div className="checkout-actions"><Link className="button" to="/account/orders">View my orders</Link><Link className="arrow-link" to={`/track?order=${encodeURIComponent(order.order_number)}`}>Track this order →</Link></div></section>;
	return <section className="section shell"><span className="eyebrow">Almost there</span><h1 className="page-title">Delivery details</h1><Notice error={error} />{loading ? <p>Loading your order…</p> : !cart.items.length ? <div className="empty-state"><p>Your cart is empty.</p><Link className="button" to="/shop">Explore flowers</Link></div> : <div className="checkout-grid"><form className="checkout-form" onSubmit={submit}><label>Recipient name<input required maxLength="100" name="recipient_name" value={form.recipient_name} onChange={update} /></label><label>Delivery address<textarea required maxLength="255" name="address" rows="3" value={form.address} onChange={update} placeholder="Enter the complete delivery address" /></label><p className="muted map-note">Enter the address now. Google Maps pinning will be added when Maps is configured; delivery is currently free.</p><label>Delivery date and time<input required type="datetime-local" min={new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16)} name="scheduled_at" value={form.scheduled_at} onChange={update} /></label><label>Card message (optional)<textarea maxLength="255" name="card_message" rows="3" value={form.card_message} onChange={update} placeholder="Add a note to the bouquet" /></label><fieldset className="payment-options"><legend>Required {paymentOptions.deposit_percent}% deposit</legend><label className="payment-choice"><input type="radio" name="payment_method" value="cash_at_shop" checked={form.payment_method === 'cash_at_shop'} onChange={update} /><span><strong>Cash deposit at the shop</strong><small>Pay ₱{depositAmount.toFixed(2)} at the shop before preparation; the rest is cash on delivery.</small></span></label><label className={`payment-choice${paymentOptions.online_available ? '' : ' disabled'}`}><input type="radio" name="payment_method" value="paymongo" checked={form.payment_method === 'paymongo'} disabled={!paymentOptions.online_available} onChange={update} /><span><strong>Pay deposit online</strong><small>{paymentOptions.online_available ? `Pay ₱${depositAmount.toFixed(2)} securely via PayMongo; the balance is due on delivery.` : 'Online checkout will be available after PayMongo server credentials are configured.'}</small></span></label></fieldset><button className="button" disabled={busy || (form.payment_method === 'paymongo' && !paymentOptions.online_available)}>{busy ? 'Placing your order…' : 'Place order'}</button></form><aside className="checkout-summary"><h2>Order summary</h2>{cart.items.map((item) => <div className="summary-line" key={item.cart_item_id}><span>{item.name} × {item.quantity}</span><strong>₱{Number(item.line_total).toFixed(2)}</strong></div>)}<div className="summary-line"><span>Delivery</span><strong>Free</strong></div><div className="summary-total"><span>Total</span><strong>₱{Number(cart.total).toFixed(2)}</strong></div><div className="summary-line deposit-line"><span>Deposit due now</span><strong>₱{depositAmount.toFixed(2)}</strong></div><div className="summary-line"><span>Balance due on delivery</span><strong>₱{(Number(cart.total) - depositAmount).toFixed(2)}</strong></div><p className="muted">Orders are confirmed and prepared only after the deposit is received. Stock is reserved when you place the order.</p></aside></div>}</section>;
}

function AboutPage() {
	return <section className="section shell narrow"><span className="eyebrow">Rooted in thoughtful giving</span><h1 className="page-title">A little joy, arranged with care.</h1><p className="lead">Justine Klein Flower Shop brings together fresh blooms and thoughtful details for the people and moments that matter.</p><div className="contact-card"><h2>Get in touch</h2><p>Email: hello@justineklein.example</p><p>Open daily, 8:00 AM – 6:00 PM</p></div></section>;
}

function CustomOrdersPage({ session }) {
	const [requests, setRequests] = useState([]);
	const [request, setRequest] = useState(null);
	const [error, setError] = useState('');
	const [busy, setBusy] = useState(false);
	const [message, setMessage] = useState('');
	const [creating, setCreating] = useState(false);
	const { id } = useParams();
	const navigate = useNavigate();
	const messagesEndRef = useRef(null);
	const [form, setForm] = useState({ title: '', occasion: '', requested_flowers: '', colors: '', budget: '', recipient_name: '', delivery_address: '', requested_delivery_at: '' });
	useEffect(() => {
		if (session.user) api('/custom-orders').then((result) => setRequests(result.requests || [])).catch((failure) => setError(failure.message));
	}, [session.user]);
	useEffect(() => {
		setError('');
		setRequest(null);
		if (!id || !session.user) return;
		setCreating(false);
		let active = true;
		const refreshConversation = async () => {
			try {
				const [conversation, list] = await Promise.all([
					api(`/custom-orders/${id}`),
					api('/custom-orders')
				]);
				if (!active) return;
				setRequest(conversation.request);
				setRequests(list.requests || []);
				setError('');
			} catch (failure) {
				if (active) setError(failure.message);
			}
		};
		refreshConversation();
		const timer = window.setInterval(refreshConversation, 5000);
		return () => {
			active = false;
			window.clearInterval(timer);
		};
	}, [id, session.user]);
	useEffect(() => {
		if (request?.messages?.length) messagesEndRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
	}, [request?.messages?.length, id]);
	async function submit(event) {
		event.preventDefault();
		setError('');
		setBusy(true);
		try {
			const result = await api('/custom-orders', { method: 'POST', csrf: session.csrf, body: JSON.stringify(form) });
			const list = await api('/custom-orders');
			setRequests(list.requests || []);
			setCreating(false);
			navigate(`/custom-orders/${result.request.custom_order_request_id}`);
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	async function sendMessage(event) {
		event.preventDefault();
		setBusy(true);
		setError('');
		try {
			const result = await api(`/custom-orders/${id}/messages`, { method: 'POST', csrf: session.csrf, body: JSON.stringify({ message }) });
			setRequest(result.request);
			setRequests((current) => current.map((item) => item.custom_order_request_id === result.request.custom_order_request_id ? result.request : item));
			setMessage('');
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	async function decide(decision) {
		setBusy(true);
		setError('');
		try {
			const result = await api(`/custom-orders/${id}/decision`, { method: 'POST', csrf: session.csrf, body: JSON.stringify({ decision }) });
			setRequest(result.request);
			setRequests((current) => current.map((item) => item.custom_order_request_id === result.request.custom_order_request_id ? result.request : item));
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	if (!session.user) return <section className="section shell narrow"><span className="eyebrow">Made just for you</span><h1 className="page-title">Request a custom arrangement</h1><p className="muted">Sign in or create an account to message our team, receive a quote, and follow your request.</p><Link className="button" to="/login">Sign in to continue</Link></section>;
	const activeId = Number(id);
	const dateLabel = (date) => date ? new Date(date.replace(' ', 'T')).toLocaleDateString([], { month: 'short', day: 'numeric' }) : '';
	return <section className="section shell message-page"><div className="message-heading"><div><span className="eyebrow">Justine Klein Flower Shop</span><h1 className="page-title">Message us</h1></div><p className="muted">Talk with our team about a custom flower arrangement.</p></div><Notice error={error} /><div className={`messenger ${id || creating ? 'has-active-chat' : ''}`}>
		<aside className="messenger-sidebar"><div className="messenger-sidebar-head"><div><b>Conversations</b><small>Your custom order messages</small></div><button className="new-message-button" type="button" onClick={() => { setCreating(true); navigate('/custom-orders'); }}>＋ New</button></div><div className="messenger-list">{requests.map((item) => <Link className={`messenger-list-item ${activeId === Number(item.custom_order_request_id) ? 'active' : ''}`} to={`/custom-orders/${item.custom_order_request_id}`} key={item.custom_order_request_id}><span className="messenger-avatar">✿</span><span className="messenger-list-copy"><span className="messenger-list-title">{item.title}</span><span className="messenger-preview">{item.requested_flowers}</span></span><span className="messenger-list-meta"><time>{dateLabel(item.updated_at)}</time><span className={`status-dot status-${item.status}`} title={item.status.replaceAll('_', ' ')} /></span></Link>)}{!requests.length && <p className="messenger-empty">No conversations yet.<br />Start a custom order to message us.</p>}</div></aside>
		<section className="messenger-chat">
			{creating ? <><div className="messenger-chat-head"><button className="mobile-back" type="button" onClick={() => setCreating(false)}>←</button><span className="messenger-avatar">✿</span><div><b>New custom order</b><small>Share what you have in mind</small></div></div><form className="messenger-request-form" onSubmit={submit}><div className="messenger-form-intro"><h2>Tell us about your flowers</h2><p>Our team will reply in this conversation with questions or a quote. Nothing is ordered until you accept the quote.</p></div><div className="messenger-form-fields"><label>Request title<input maxLength="120" value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} required /></label><label>Occasion<input maxLength="80" value={form.occasion} onChange={(event) => setForm({ ...form, occasion: event.target.value })} placeholder="Birthday, anniversary..." /></label><label className="wide-field">Describe the flowers<textarea rows="3" maxLength="5000" value={form.requested_flowers} onChange={(event) => setForm({ ...form, requested_flowers: event.target.value })} placeholder="Flower types, arrangement size, special details..." required /></label><label>Preferred colors<input maxLength="255" value={form.colors} onChange={(event) => setForm({ ...form, colors: event.target.value })} /></label><label>Budget (₱)<input type="number" min="0.01" step="0.01" value={form.budget} onChange={(event) => setForm({ ...form, budget: event.target.value })} placeholder="Optional" /></label><label>Recipient name<input maxLength="100" value={form.recipient_name} onChange={(event) => setForm({ ...form, recipient_name: event.target.value })} required /></label><label>Delivery address<input maxLength="255" value={form.delivery_address} onChange={(event) => setForm({ ...form, delivery_address: event.target.value })} required /></label><label className="wide-field">Preferred delivery date and time<input type="datetime-local" value={form.requested_delivery_at} onChange={(event) => setForm({ ...form, requested_delivery_at: event.target.value })} required /></label></div><div className="messenger-form-footer"><button className="button" disabled={busy}>{busy ? 'Sending request…' : 'Start conversation'}</button></div></form></> : request ? <><div className="messenger-chat-head"><button className="mobile-back" type="button" onClick={() => navigate('/custom-orders')}>←</button><span className="messenger-avatar">✿</span><div className="messenger-contact"><b>{request.title}</b><small>{request.request_number} · Justine Klein Flower Shop</small></div><span className={`status-pill status-${request.status}`}>{request.status.replaceAll('_', ' ')}</span><button className="chat-details-button" type="button" onClick={(event) => event.currentTarget.nextElementSibling.classList.toggle('open')}>Details</button><div className="chat-details-popover"><b>Custom order details</b><p><b>Occasion:</b> {request.occasion || 'Not specified'}</p><p><b>Flowers:</b> {request.requested_flowers}</p><p><b>Colors:</b> {request.colors || 'Not specified'}</p><p><b>Budget:</b> {request.budget ? `₱${Number(request.budget).toFixed(2)}` : 'Not specified'}</p><p><b>Delivery:</b> {request.recipient_name}, {request.delivery_address}</p><p><b>Preferred time:</b> {request.requested_delivery_at ? new Date(request.requested_delivery_at.replace(' ', 'T')).toLocaleString() : 'Not set'}</p></div></div><div className="messenger-messages"><div className="chat-date-separator"><span>{request.request_number}</span></div>{request.messages.map((item) => <article className={`chat-bubble-row ${Number(item.user_id) === Number(session.user.user_id) ? 'from-customer' : 'from-shop'}`} key={item.custom_order_message_id}><span className="chat-avatar">{['admin', 'staff'].includes(item.author_role) ? '✿' : session.user.full_name.slice(0, 1).toUpperCase()}</span><div className="chat-bubble-content"><div className="chat-author">{Number(item.user_id) === Number(session.user.user_id) ? 'You' : 'Justine Klein Flower Shop'} <time>{new Date(item.created_at.replace(' ', 'T')).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' })}</time></div><p className="chat-bubble">{item.message}</p></div></article>)}<div ref={messagesEndRef} /></div>{request.status === 'quoted' && <div className="messenger-quote-bar"><div><small>Our quote</small><strong>₱{Number(request.quote_total).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></div><div className="decision-actions"><button className="button" disabled={busy} onClick={() => decide('accept')}>Accept quote</button><button className="button button-secondary" disabled={busy} onClick={() => decide('decline')}>Decline</button></div></div>}{!['declined', 'converted_to_order', 'closed', 'accepted'].includes(request.status) ? <form className="messenger-composer" onSubmit={sendMessage}><textarea value={message} maxLength="5000" onChange={(event) => setMessage(event.target.value)} placeholder="Type a message..." required /><button className="send-message-button" disabled={busy || !message.trim()} aria-label="Send message">{busy ? '…' : '➤'}</button></form> : <div className="messenger-closed-note">{request.status === 'accepted' ? 'Quote accepted. The shop will convert it into an order.' : `This conversation is ${request.status.replaceAll('_', ' ')}.`}</div>}</> : error ? <div className="messenger-placeholder"><span className="messenger-placeholder-mark">✿</span><p>{error}</p></div> : <div className="messenger-placeholder"><span className="messenger-placeholder-mark">✿</span><h2>Your flower chat</h2><p>Choose a conversation or start a new custom-order request to talk with our team.</p><button className="button" type="button" onClick={() => setCreating(true)}>＋ New custom order</button></div>}
		</section></div></section>;
}

function PlaceholderPage({ title, session }) {
	return <section className="section shell narrow"><span className="eyebrow">Justine Klein Flower Shop</span><h1 className="page-title">{title}</h1><p className="muted">{session.user ? 'This screen is part of the React storefront foundation. Its data actions will be connected in the next implementation phase.' : 'Sign in to continue.'}</p><Link className="button" to={session.user ? '/' : '/login'}>{session.user ? 'Back to home' : 'Sign in'}</Link></section>;
}

function AdminLayout({ session, setSession, basePath, children }) {
	const navigate = useNavigate();
	const isAdmin = session.user.role === 'admin';
	const nav = isAdmin
		? [['', 'Overview'], ['/products', 'Products'], ['/orders', 'Orders'], ['/custom-orders', 'Messages'], ['/deliveries', 'Deliveries'], ['/inventory', 'Inventory'], ['/staff', 'Staff'], ['/reports', 'Reports']]
		: [['', 'Overview'], ['/orders', 'Orders'], ['/custom-orders', 'Messages'], ['/deliveries', 'Deliveries'], ['/inventory', 'Inventory']];
	return <div className="admin-layout"><aside className="admin-sidebar"><Link className="admin-brand" to={basePath}>✿ Justine Klein<small>{isAdmin ? 'ADMIN WORKSPACE' : 'STAFF WORKSPACE'}</small></Link>{nav.map(([path, label]) => { const to = `${basePath}${path}`; return <Link className={window.location.pathname === to ? 'active' : ''} to={to} key={to}>{label}</Link>; })}<Link to="/">← Customer shop</Link></aside><section className="admin-content"><div className="admin-top"><span>Good day, {session.user.full_name}</span><button className="text-button" type="button" onClick={async () => { try { const result = await api('/auth/logout', { method: 'POST', csrf: session.csrf }); setSession({ ready: true, user: null, csrf: result.csrf }); navigate('/login'); } catch (error) { window.alert(error.message); } }}>Sign out</button></div>{children}</section></div>;
}

function AdminDashboard({ session, setSession, basePath }) {
	const [dashboard, setDashboard] = useState(null);
	const [error, setError] = useState('');
	useEffect(() => { api('/admin/dashboard').then(setDashboard).catch((failure) => setError(failure.message)); }, []);
	return <AdminLayout session={session} setSession={setSession} basePath={basePath}><span className="eyebrow">{session.user.role === 'admin' ? 'Shop overview' : 'Daily operations'}</span><h1 className="page-title">{session.user.role === 'admin' ? 'Admin dashboard' : 'Staff dashboard'}</h1><Notice error={error} /><div className="metric-grid">{[['Pending orders', dashboard?.pending], ['Out for delivery', dashboard?.delivering], ['Low-stock bouquets', dashboard?.low_stock], ...(session.user.role === 'admin' ? [['Sales this week', dashboard ? `₱${Number(dashboard.week_sales).toFixed(2)}` : '—']] : [])].map(([label, value]) => <article className="metric-card" key={label}><span>{label}</span><strong>{value ?? '—'}</strong></article>)}</div><h2>Recent orders</h2><div className="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead><tbody>{(dashboard?.recent || []).map((order) => <tr key={order.order_id}><td>{order.order_number}</td><td>{order.customer}</td><td>₱{Number(order.total).toFixed(2)}</td><td>{order.status.replaceAll('_', ' ')}</td></tr>)}</tbody></table></div></AdminLayout>;
}

function AdminPage({ session, setSession, staffWorkspace = false }) {
	const location = useLocation();
	const isAdmin = session.user?.role === 'admin';
	const isStaff = session.user?.role === 'staff';
	if (!isAdmin && !isStaff) return session.user ? <Navigate to="/" replace /> : <AuthPage mode="login" session={session} setSession={setSession} />;
	if (staffWorkspace !== isStaff) return <Navigate to={isStaff ? '/staff' : '/admin'} replace />;
	const basePath = staffWorkspace ? '/staff' : '/admin';
	const section = location.pathname.replace(/^\/admin\/?/, '').split('/')[0];
	const workspaceSection = staffWorkspace ? location.pathname.replace(/^\/staff\/?/, '').split('/')[0] : section;
	if (!workspaceSection) return <AdminDashboard session={session} setSession={setSession} basePath={basePath} />;
	if (staffWorkspace && !['orders', 'custom-orders', 'deliveries', 'inventory'].includes(workspaceSection)) return <Navigate to="/staff" replace />;
	if (section === 'products' && location.pathname === '/admin/products/create') {
		return <AdminLayout session={session} setSession={setSession} basePath={basePath}><AdminProductForm session={session} /></AdminLayout>;
	}
	if (workspaceSection === 'custom-orders' && location.pathname === `${basePath}/custom-orders`) {
		return <AdminLayout session={session} setSession={setSession} basePath={basePath}><AdminCustomOrderQueue basePath={basePath} /></AdminLayout>;
	}
	const customOrderMatch = location.pathname.match(/^\/(?:admin|staff)\/custom-orders\/(\d+)$/);
	if (customOrderMatch) return <AdminLayout session={session} setSession={setSession} basePath={basePath}><AdminCustomOrderThread requestId={Number(customOrderMatch[1])} csrf={session.csrf} basePath={basePath} /></AdminLayout>;
	const editMatch = location.pathname.match(/^\/admin\/products\/(\d+)\/edit$/);
	if (editMatch) return <AdminLayout session={session} setSession={setSession} basePath={basePath}><AdminProductForm session={session} productId={Number(editMatch[1])} /></AdminLayout>;
	const viewMatch = location.pathname.match(/^\/admin\/products\/(\d+)(?:\/delete)?$/);
	if (viewMatch) return <AdminLayout session={session} setSession={setSession} basePath={basePath}><AdminProductView productId={Number(viewMatch[1])} csrf={session.csrf} /></AdminLayout>;
	const title = ({ products: 'Products', orders: 'Orders', deliveries: 'Deliveries', inventory: 'Inventory', staff: 'Staff', reports: 'Sales reports' })[workspaceSection] || 'Shop management';
	return <AdminLayout session={session} setSession={setSession} basePath={basePath}><AdminSection section={workspaceSection} title={title} csrf={session.csrf} /></AdminLayout>;
}

function AdminCustomOrderQueue({ basePath }) {
	const [requests, setRequests] = useState([]);
	const [error, setError] = useState('');
	useEffect(() => {
		let active = true;
		const refresh = () => api('/admin/custom-orders')
			.then((result) => { if (active) { setRequests(result.requests || []); setError(''); } })
			.catch((failure) => { if (active) setError(failure.message); });
		refresh();
		const timer = window.setInterval(refresh, 5000);
		return () => { active = false; window.clearInterval(timer); };
	}, []);
	return <><span className="eyebrow">Customer requests</span><h1 className="page-title">Custom orders</h1><Notice error={error} /><div className="custom-request-list">{requests.map((request) => <Link className="custom-request-card" to={`${basePath}/custom-orders/${request.custom_order_request_id}`} key={request.custom_order_request_id}><div className="custom-request-head"><span className="eyebrow">{request.request_number}</span><span className={`status-pill status-${request.status}`}>{request.status.replaceAll('_', ' ')}</span></div><h3>{request.title}</h3><p>{request.customer_name} · {request.customer_email}</p><p className="muted">{request.requested_flowers}</p></Link>)}{!requests.length && !error && <p className="empty-state">There are no custom-order requests yet.</p>}</div></>;
}

function AdminCustomOrderThread({ requestId, csrf, basePath }) {
	const [request, setRequest] = useState(null);
	const [error, setError] = useState('');
	const [message, setMessage] = useState('');
	const [quote, setQuote] = useState('');
	const [busy, setBusy] = useState(false);
	const messagesEndRef = useRef(null);
	useEffect(() => {
		let active = true;
		const refresh = () => api(`/admin/custom-orders/${requestId}`)
			.then((result) => { if (active) { setRequest(result.request); setError(''); } })
			.catch((failure) => { if (active) setError(failure.message); });
		refresh();
		const timer = window.setInterval(refresh, 5000);
		return () => { active = false; window.clearInterval(timer); };
	}, [requestId]);
	useEffect(() => {
		if (request?.messages?.length) messagesEndRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
	}, [request?.messages?.length, requestId]);
	async function reply(event) {
		event.preventDefault();
		setBusy(true);
		setError('');
		try {
			const result = await api(`/admin/custom-orders/${requestId}/messages`, { method: 'POST', csrf, body: JSON.stringify({ message, quote_total: quote }) });
			setRequest(result.request); setMessage(''); setQuote('');
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	async function convert() {
		setBusy(true);
		setError('');
		try {
			await api(`/admin/custom-orders/${requestId}/convert`, { method: 'POST', csrf, body: JSON.stringify({}) });
			const result = await api(`/admin/custom-orders/${requestId}`);
			setRequest(result.request);
		} catch (failure) { setError(failure.message); }
		finally { setBusy(false); }
	}
	if (!request) return <><Link className="thread-back-link" to={`${basePath}/custom-orders`}>← Back to custom orders</Link><Notice error={error} />{!error && <p>Loading request…</p>}</>;
	const closed = ['declined', 'converted_to_order', 'closed'].includes(request.status);
	const showQuote = ['quoted', 'accepted'].includes(request.status) && request.quote_total;
	const dateLabel = (value) => value ? new Date(value.replace(' ', 'T')).toLocaleString() : 'Not specified';
	return <section className="custom-thread">
		<Link className="thread-back-link" to={`${basePath}/custom-orders`}>← Back to custom orders</Link>
		<Notice error={error} />
		<header className="custom-thread-heading">
			<div><span className="eyebrow">{request.request_number}</span><h1 className="page-title">{request.title}</h1><p className="muted">{request.customer_name} · {request.customer_email}</p></div>
			<span className={`status-pill status-${request.status}`}>{request.status.replaceAll('_', ' ')}</span>
		</header>
		<dl className="custom-thread-details">
			<div><dt>Occasion</dt><dd>{request.occasion || 'Not specified'}</dd></div>
			<div><dt>Flowers and details</dt><dd>{request.requested_flowers}</dd></div>
			<div><dt>Preferred colors</dt><dd>{request.colors || 'Not specified'}</dd></div>
			<div><dt>Customer budget</dt><dd>{request.budget ? `₱${Number(request.budget).toFixed(2)}` : 'Not specified'}</dd></div>
			<div><dt>Recipient</dt><dd>{request.recipient_name}</dd></div>
			<div><dt>Delivery address</dt><dd>{request.delivery_address}</dd></div>
			<div><dt>Requested delivery</dt><dd>{dateLabel(request.requested_delivery_at)}</dd></div>
		</dl>
		<div className="custom-thread-messages" aria-label="Conversation messages" aria-live="polite">
			{request.messages.map((item) => {
				const fromCustomer = item.author_role === 'customer';
				return <article className={`chat-bubble-row ${fromCustomer ? 'from-customer' : 'from-shop'}`} key={item.custom_order_message_id}>
					<span className="chat-avatar">{fromCustomer ? request.customer_name.slice(0, 1).toUpperCase() : '✿'}</span>
					<div className="chat-bubble-content"><div className="chat-author">{fromCustomer ? request.customer_name : `${item.full_name} · Shop`} <time>{dateLabel(item.created_at)}</time></div><p className="chat-bubble">{item.message}</p></div>
				</article>;
			})}
			<div ref={messagesEndRef} />
		</div>
		{showQuote && <div className="custom-thread-quote"><div><small>{request.status === 'accepted' ? 'Accepted quote' : 'Current quote'}</small><strong>₱{Number(request.quote_total).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong></div>{request.status === 'accepted' && <button className="button" type="button" disabled={busy} onClick={convert}>{busy ? 'Converting…' : 'Convert accepted quote to order'}</button>}</div>}
		{!closed ? <form className="custom-thread-composer" onSubmit={reply}>
			<label>Reply to customer<textarea value={message} maxLength="5000" onChange={(event) => setMessage(event.target.value)} placeholder="Write a message or explain your quote..." required /></label>
			<div className="custom-thread-composer-footer"><label>Quote amount (₱)<input type="number" min="0.01" step="0.01" value={quote} onChange={(event) => setQuote(event.target.value)} placeholder="Leave blank for a message only" /></label><button className="button" disabled={busy || !message.trim()}>{busy ? 'Sending…' : quote ? 'Send quote' : 'Send reply'}</button></div>
		</form> : <p className="custom-thread-closed">This conversation is {request.status.replaceAll('_', ' ')} and can no longer be changed.</p>}
	</section>;
}

function AdminSection({ section, title, csrf }) {
	const [data, setData] = useState(null);
	const [error, setError] = useState('');
	useEffect(() => { api(`/admin/${section}`).then(setData).catch((failure) => setError(failure.message)); }, [section]);
	const listKey = ({ products: 'products', inventory: 'products', orders: 'orders', deliveries: 'deliveries', staff: 'staff' })[section];
	const rows = listKey && data ? data[listKey] || [] : [];
	const columns = {
		products: [['Product', 'name'], ['Occasion', 'category_name'], ['Price', 'price'], ['Stock', 'stock_qty']],
		inventory: [['Product', 'name'], ['Stock', 'stock_qty'], ['Low stock at', 'low_stock_level']],
		orders: [['Order', 'order_number'], ['Type', 'custom_order_title'], ['Customer', 'customer'], ['Total', 'total'], ['Status', 'status'], ['Date', 'created_at']],
		staff: [['Name', 'full_name'], ['Email', 'email'], ['Phone', 'phone'], ['Role', 'role_name']]
	}[section] || [];
	return <><span className="eyebrow">Shop management</span><h1 className="page-title">{title}</h1><Notice error={error} />	{section === 'reports' && data ? <><p className="muted">Sales totals include non-cancelled custom orders. Best sellers lists catalog products only.</p><h2>Recent sales</h2><DataTable rows={data.days || []} columns={[['Day', 'day'], ['Orders', 'orders'], ['Sales', 'sales']]} /><h2 className="report-subhead">Best sellers</h2><DataTable rows={data.top || []} columns={[['Product', 'name'], ['Quantity', 'qty'], ['Revenue', 'revenue']]} /></> : data && section === 'products' ? <><Link className="button admin-add-button" to="/admin/products/create">Add product</Link><ProductTable products={rows} onUpdate={setData} csrf={csrf} /></> : data && section === 'orders' ? <AdminOrdersTable orders={rows} onUpdate={setData} csrf={csrf} /> : data && section === 'deliveries' ? <AdminDeliveriesTable deliveries={rows} onUpdate={setData} csrf={csrf} /> : data && section === 'staff' ? <AdminStaffTable staff={rows} onUpdate={setData} csrf={csrf} /> : data ? <DataTable rows={rows} columns={columns} /> : !error ? <p>Loading {title.toLocaleLowerCase()}…</p> : null}</>;
}

function AdminOrdersTable({ orders, onUpdate, csrf }) {
	const [error, setError] = useState('');
	const [showArchived, setShowArchived] = useState(false);
	const visibleOrders = orders.filter((order) => Boolean(order.operations_archived_at) === showArchived);
	const archivedCount = orders.filter((order) => Boolean(order.operations_archived_at)).length;
	function updateOrder(orderId, updates) {
		onUpdate((current) => ({ ...current, orders: current.orders.map((item) => item.order_id === orderId ? { ...item, ...updates } : item) }));
	}
	async function archive(order, archived) {
		setError('');
		try {
			const result = await api(`/admin/orders/${order.order_id}/archive`, { method: 'PATCH', csrf, body: JSON.stringify({ archived }) });
			updateOrder(order.order_id, { operations_archived_at: result.archived_at });
		} catch (failure) { setError(failure.message); }
	}
	async function confirmDeposit(order) {
		setError('');
		try {
			await api(`/admin/orders/${order.order_id}/deposit`, { method: 'PATCH', csrf });
			updateOrder(order.order_id, { status: 'confirmed', deposit_status: 'paid' });
		} catch (failure) { setError(failure.message); }
	}
	async function advance(order, status) {
		setError('');
		try {
			const result = await api(`/admin/orders/${order.order_id}/status`, { method: 'PATCH', csrf, body: JSON.stringify({ status }) });
			updateOrder(order.order_id, result.order);
		} catch (failure) { setError(failure.message); }
	}
	async function recordBalance(order) {
		setError('');
		try {
			await api(`/admin/orders/${order.order_id}/balance`, { method: 'PATCH', csrf });
			updateOrder(order.order_id, { balance_status: 'paid' });
		} catch (failure) { setError(failure.message); }
	}
	async function recordRefund(order) {
		const remaining = Math.max(0, Number(order.deposit_amount || 0) - Number(order.deposit_refunded || 0));
		if (!remaining) return;
		const amount = window.prompt(`Enter the deposit refund amount already paid to the customer (up to ₱${remaining.toFixed(2)}). This only records a manual refund; it does not transfer money.`, remaining.toFixed(2));
		if (amount === null) return;
		const refund = Number(amount);
		if (!Number.isFinite(refund) || refund <= 0 || refund > remaining) {
			setError(`Enter an amount greater than ₱0.00 and no more than ₱${remaining.toFixed(2)}.`);
			return;
		}
		setError('');
		try {
			const result = await api(`/admin/orders/${order.order_id}/refund`, { method: 'POST', csrf, body: JSON.stringify({ amount: refund.toFixed(2) }) });
			updateOrder(order.order_id, { deposit_refunded: result.refund.refunded_amount, deposit_status: result.refund.refund_status });
		} catch (failure) { setError(failure.message); }
	}
	return <><Notice error={error} /><div className="record-list-controls"><button className="text-button" type="button" disabled={!showArchived && !archivedCount} onClick={() => setShowArchived((current) => !current)}>{showArchived ? 'Show active orders' : `Show archived orders (${archivedCount})`}</button></div>{visibleOrders.length ? <div className="table-wrap"><table><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Deposit</th><th>Balance</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>{visibleOrders.map((order) => {
		const nextStatus = { confirmed: 'preparing', preparing: 'out_for_delivery' }[order.status];
		const refundRemaining = Number(order.deposit_amount || 0) - Number(order.deposit_refunded || 0);
		return <tr key={order.order_id}>
			<td>{order.order_number}<small className="admin-order-type">{order.custom_order_title || 'Catalog order'}</small></td>
			<td>{order.customer}</td><td>₱{Number(order.total).toFixed(2)}</td>
			<td>₱{Number(order.deposit_amount || 0).toFixed(2)} · {order.deposit_status || '—'}{order.deposit_received_by && <small className="refund-summary">Received by {order.deposit_received_by}</small>}{Number(order.deposit_refunded) > 0 && <small className="refund-summary">₱{Number(order.deposit_refunded).toFixed(2)} refunded</small>}</td>
			<td>₱{Number(order.balance_amount || 0).toFixed(2)} · {order.balance_status || '—'}{order.balance_received_by && <small className="refund-summary">Received by {order.balance_received_by}</small>}</td>
			<td><StatusBadge status={order.status} /></td><td>{String(order.created_at).slice(0, 10)}</td>
			<td className="admin-order-actions">
				{order.payment_method === 'cash' && order.deposit_status === 'pending' && <button className="text-button" type="button" onClick={() => confirmDeposit(order)}>Confirm cash deposit</button>}
				{nextStatus && <button className="text-button" type="button" disabled={(nextStatus === 'preparing' && order.deposit_status !== 'paid') || (nextStatus === 'out_for_delivery' && !order.assigned_to)} onClick={() => advance(order, nextStatus)}>{({ preparing: 'Start preparation', out_for_delivery: 'Dispatch order' })[nextStatus]}</button>}
				{order.status === 'out_for_delivery' && order.balance_status === 'pending' && <button className="text-button" type="button" onClick={() => recordBalance(order)}>Record cash balance</button>}
				{order.status === 'cancelled' && ['paid', 'partially_refunded'].includes(order.deposit_status) && refundRemaining > 0 && <button className="text-button" type="button" onClick={() => recordRefund(order)}>Record manual refund</button>}
				{['delivered', 'cancelled'].includes(order.status) && <button className="text-button" type="button" onClick={() => archive(order, !showArchived)}>{showArchived ? 'Restore' : 'Archive'}</button>}
			</td>
		</tr>;
	})}</tbody></table></div> : <p className="empty-state">{showArchived ? 'No archived orders.' : 'No active orders to display.'}</p>}<p className="muted">Assign a rider in Deliveries before dispatch. The assigned rider confirms handoff with the customer's PIN and recipient name; COD collection is recorded with that confirmation. Refund entries only record money already returned manually. Archiving only removes closed orders from this work list; it preserves order and payment history.</p></>;
}

function AdminDeliveriesTable({ deliveries, onUpdate, csrf }) {
	const [riderOptions, setRiderOptions] = useState([]);
	const [assigned, setAssigned] = useState({});
	const [assignedRiderNames, setAssignedRiderNames] = useState({});
	const [error, setError] = useState('');
	const [busyId, setBusyId] = useState(null);
	const [showArchived, setShowArchived] = useState(false);
	const visibleDeliveries = deliveries.filter((delivery) => Boolean(delivery.operations_archived_at) === showArchived);
	const archivedCount = deliveries.filter((delivery) => Boolean(delivery.operations_archived_at)).length;
	useEffect(() => {
		api('/admin/riders').then((result) => setRiderOptions(result.riders)).catch((failure) => setError(failure.message));
	}, []);
	async function archive(delivery, archived) {
		setError('');
		setBusyId(delivery.delivery_id);
		try {
			const result = await api(`/admin/deliveries/${delivery.delivery_id}/archive`, { method: 'PATCH', csrf, body: JSON.stringify({ archived }) });
			onUpdate((current) => ({ ...current, deliveries: current.deliveries.map((item) => item.delivery_id === delivery.delivery_id ? { ...item, operations_archived_at: result.archived_at } : item) }));
		} catch (failure) { setError(failure.message); }
		finally { setBusyId(null); }
	}
	async function assign(delivery) {
		const staffId = Number(assigned[delivery.delivery_id] || delivery.assigned_to);
		if (!staffId) { setError('Select a staff member before saving the rider assignment.'); return; }
		const selected = riderOptions.find((person) => Number(person.user_id) === staffId);
		if (!selected) { setError('Select a rider from the available list.'); return; }
		setError('');
		setBusyId(delivery.delivery_id);
		try {
			await api(`/admin/deliveries/${delivery.delivery_id}/rider`, { method: 'PATCH', csrf, body: JSON.stringify({ assigned_to: staffId }) });
			setAssignedRiderNames((current) => ({ ...current, [delivery.delivery_id]: selected.full_name }));
			setAssigned((current) => ({ ...current, [delivery.delivery_id]: String(staffId) }));
		} catch (failure) { setError(failure.message); }
		finally { setBusyId(null); }
	}
	return <><Notice error={error} /><div className="record-list-controls"><button className="text-button" type="button" disabled={!showArchived && !archivedCount} onClick={() => setShowArchived((current) => !current)}>{showArchived ? 'Show active deliveries' : `Show archived deliveries (${archivedCount})`}</button></div>{visibleDeliveries.length ? <div className="admin-delivery-list">{visibleDeliveries.map((delivery) => {
		const canAssign = ['confirmed', 'preparing'].includes(delivery.order_status) && ['scheduled', 'preparing'].includes(delivery.status);
		const canArchive = ['delivered', 'cancelled', 'failed'].includes(delivery.status) && ['delivered', 'cancelled'].includes(delivery.order_status);
		return <article className="admin-delivery-card" key={delivery.delivery_id}>
			<header className="admin-delivery-card-header">
				<h2>{delivery.order_number}</h2>
				{canArchive && <button className="text-button" type="button" disabled={busyId === delivery.delivery_id} onClick={() => archive(delivery, !showArchived)}>{busyId === delivery.delivery_id ? 'Saving…' : showArchived ? 'Restore' : 'Archive'}</button>}
			</header>
			<div className="admin-delivery-fields">
				<div><span>Recipient / address</span><strong>{delivery.recipient_name}</strong><small>{delivery.address}</small></div>
				<div><span>Schedule</span><strong>{new Date(delivery.scheduled_at.replace(' ', 'T')).toLocaleString()}</strong></div>
				<div><span>Order status</span><StatusBadge status={delivery.order_status} /></div>
				<div><span>Delivery status</span><StatusBadge status={delivery.status} /></div>
				<div><span>Rider assignment</span>{canAssign ? <div className="rider-assignment"><select aria-label={`Assign rider for ${delivery.order_number}`} value={assigned[delivery.delivery_id] ?? String(delivery.assigned_to || '')} onChange={(event) => setAssigned((current) => ({ ...current, [delivery.delivery_id]: event.target.value }))}><option value="">Select rider</option>{riderOptions.map((person) => <option value={person.user_id} key={person.user_id}>{person.full_name}</option>)}</select><button className="text-button" type="button" disabled={busyId === delivery.delivery_id || !riderOptions.length} onClick={() => assign(delivery)}>{busyId === delivery.delivery_id ? 'Saving…' : 'Save'}</button></div> : <strong>{assignedRiderNames[delivery.delivery_id] || delivery.rider || '—'}</strong>}</div>
				<div className="admin-delivery-confirmation"><span>Confirmation</span>{delivery.delivered_at ? <><strong>Delivered to {delivery.delivered_to}</strong><small>Confirmed by {delivery.confirmed_by_name || 'Rider'} on {new Date(delivery.delivered_at.replace(' ', 'T')).toLocaleString()}</small></> : <strong>Awaiting rider confirmation</strong>}</div>
			</div>
		</article>;
	})}</div> : <p className="empty-state">{showArchived ? 'No archived deliveries.' : 'No active deliveries to display.'}</p>}<p className="muted">Promote staff to the Rider role in Staff before assigning them. Riders confirm handoff using the recipient PIN. Archiving only removes closed deliveries from this work list; it preserves confirmation history.</p></>;
}

function AdminStaffTable({ staff, onUpdate, csrf }) {
	const [error, setError] = useState('');
	const [busyId, setBusyId] = useState(null);
	async function changeRole(person, role) {
		setError('');
		setBusyId(Number(person.user_id));
		try {
			await api(`/admin/staff/${person.user_id}/role`, { method: 'PATCH', csrf, body: JSON.stringify({ role }) });
			onUpdate((current) => ({ ...current, staff: current.staff.map((item) => item.user_id === person.user_id ? { ...item, role_name: role } : item) }));
		} catch (failure) { setError(failure.message); }
		finally { setBusyId(null); }
	}
	return <><Notice error={error} /><div className="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th></tr></thead><tbody>{staff.map((person) => <tr key={person.user_id}><td>{person.full_name}</td><td>{person.email}</td><td>{person.phone || '—'}</td><td>{person.role_name === 'admin' ? 'Admin' : <select aria-label={`Role for ${person.full_name}`} value={person.role_name} disabled={busyId === Number(person.user_id)} onChange={(event) => changeRole(person, event.target.value)}><option value="staff">Staff</option><option value="rider">Rider</option></select>}</td></tr>)}</tbody></table></div><p className="muted">Set a team member to Rider to give them access to their assigned deliveries. Riders cannot access staff or admin tools.</p></>;
}

function RiderPage({ session, setSession }) {
	const navigate = useNavigate();
	const [deliveries, setDeliveries] = useState([]);
	const [forms, setForms] = useState({});
	const [error, setError] = useState('');
	const [success, setSuccess] = useState('');
	const [loading, setLoading] = useState(true);
	const [busyId, setBusyId] = useState(null);
	useEffect(() => {
		if (session.user?.role !== 'rider') return;
		api('/rider/deliveries')
			.then((result) => setDeliveries(result.deliveries || []))
			.catch((failure) => setError(failure.message))
			.finally(() => setLoading(false));
	}, [session.user]);
	if (!session.user) return <Navigate to="/login" replace />;
	if (session.user.role !== 'rider') return <Navigate to={session.user.role === 'admin' ? '/admin' : '/staff'} replace />;
	function updateForm(deliveryId, field, value) {
		setForms((current) => ({ ...current, [deliveryId]: { ...(current[deliveryId] || {}), [field]: value } }));
	}
	async function confirm(delivery) {
		const form = forms[delivery.delivery_id] || {};
		setError('');
		setSuccess('');
		setBusyId(delivery.delivery_id);
		try {
			await api(`/rider/deliveries/${delivery.delivery_id}/confirm`, {
				method: 'POST',
				csrf: session.csrf,
				body: JSON.stringify({ pin: form.pin || '', delivered_to: form.delivered_to || '', cash_collected: Boolean(form.cash_collected) })
			});
			setDeliveries((current) => current.filter((item) => item.delivery_id !== delivery.delivery_id));
			setSuccess(`Delivery ${delivery.order_number} confirmed.`);
		} catch (failure) { setError(failure.message); }
		finally { setBusyId(null); }
	}
	async function logout() {
		try {
			const result = await api('/auth/logout', { method: 'POST', csrf: session.csrf });
			setSession({ ready: true, user: null, csrf: result.csrf });
			navigate('/login');
		} catch (failure) { setError(failure.message); }
	}
	return <div className="rider-workspace"><header className="rider-topbar"><Link className="admin-brand" to="/rider">✿ Justine Klein<small>RIDER PORTAL</small></Link><div><span>{session.user.full_name}</span><button className="text-button" type="button" onClick={logout}>Sign out</button></div></header><main className="rider-content"><span className="eyebrow">Your delivery route</span><h1 className="page-title">Assigned deliveries</h1><p className="muted">At handoff, ask the recipient for their PIN, record the name of the person receiving the flowers, and confirm any cash collected.</p><Notice error={error} />{success && <p className="notice delivery-success" role="status">{success}</p>}{loading ? <p>Loading your deliveries…</p> : deliveries.length ? <div className="rider-delivery-list">{deliveries.map((delivery) => {
		const form = forms[delivery.delivery_id] || {};
		const balanceDue = Number(delivery.balance_amount || 0);
		const needsCash = delivery.balance_status === 'pending' && balanceDue > 0;
		const ready = /^\d{6}$/.test(form.pin || '') && Boolean((form.delivered_to || '').trim()) && (!needsCash || form.cash_collected);
		return <article className="rider-delivery-card" key={delivery.delivery_id}><div className="rider-card-heading"><div><span className="eyebrow">{delivery.order_number}</span><h2>{delivery.recipient_name}</h2></div><StatusBadge status={delivery.order_status} /></div><p><b>Delivery address</b><br />{delivery.address}</p>{delivery.customer_phone && <p><b>Customer phone</b><br /><a href={`tel:${delivery.customer_phone}`}>{delivery.customer_phone}</a></p>}<p><b>Scheduled</b><br />{new Date(delivery.scheduled_at.replace(' ', 'T')).toLocaleString()}</p>{needsCash && <p className="rider-cash-due"><b>Cash due on delivery: ₱{balanceDue.toFixed(2)}</b></p>}<div className="rider-confirm-form"><label>Recipient PIN<input inputMode="numeric" autoComplete="one-time-code" maxLength="6" pattern="[0-9]{6}" value={form.pin || ''} onChange={(event) => updateForm(delivery.delivery_id, 'pin', event.target.value.replace(/\D/g, '').slice(0, 6))} placeholder="6-digit PIN" required /></label><label>Received by<input maxLength="100" value={form.delivered_to || ''} onChange={(event) => updateForm(delivery.delivery_id, 'delivered_to', event.target.value)} placeholder="Recipient's name" required /></label>{needsCash && <label className="rider-cash-check"><input type="checkbox" checked={Boolean(form.cash_collected)} onChange={(event) => updateForm(delivery.delivery_id, 'cash_collected', event.target.checked)} /> I collected ₱{balanceDue.toFixed(2)} cash</label>}<button className="button" type="button" disabled={!ready || busyId === delivery.delivery_id} onClick={() => confirm(delivery)}>{busyId === delivery.delivery_id ? 'Confirming…' : 'Confirm delivery'}</button></div></article>;
	})}</div> : <p className="empty-state">You have no deliveries assigned that are out for delivery.</p>}</main></div>;
}

function ProductTable({ products, onUpdate, csrf }) {
	const [error, setError] = useState('');
	async function archive(product) {
		setError('');
		try {
			const result = await api(`/admin/products/${product.product_id}/archive`, { method: 'PATCH', csrf });
			onUpdate((current) => ({ ...current, products: current.products.map((item) => item.product_id === product.product_id ? { ...item, is_archived: result.is_archived ? 1 : 0 } : item) }));
		} catch (failure) { setError(failure.message); }
	}
	async function remove(product) {
		if (!window.confirm(`Delete "${product.name}"? This cannot be undone.`)) return;
		setError('');
		try {
			await api(`/admin/products/${product.product_id}`, { method: 'DELETE', csrf });
			onUpdate((current) => ({ ...current, products: current.products.filter((item) => item.product_id !== product.product_id) }));
		} catch (failure) { setError(failure.message); }
	}
	return <><Notice error={error} /><div className="table-wrap"><table><thead><tr><th>Product</th><th>Occasion</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead><tbody>{products.map((product) => <tr key={product.product_id}><td><Link to={`/admin/products/${product.product_id}`}>{product.name}</Link></td><td>{product.category_name}</td><td>₱{Number(product.price).toFixed(2)}</td><td>{product.stock_qty}</td><td>{Number(product.is_archived) ? 'Archived' : 'Active'}</td><td className="product-actions"><Link to={`/admin/products/${product.product_id}/edit`}>Edit</Link><button className="text-button" type="button" onClick={() => archive(product)}>{Number(product.is_archived) ? 'Restore' : 'Archive'}</button><button className="text-button" type="button" onClick={() => remove(product)}>Delete</button></td></tr>)}</tbody></table></div></>;
}

function AdminProductForm({ session, productId }) {
	const navigate = useNavigate();
	const [categories, setCategories] = useState([]);
	const [form, setForm] = useState({ category_id: '', name: '', description: '', price: '', stock_qty: '0', low_stock_level: '5' });
	const [error, setError] = useState('');
	const [validation, setValidation] = useState({});
	const [loading, setLoading] = useState(Boolean(productId));
	const [busy, setBusy] = useState(false);
	useEffect(() => {
		const requests = [api('/admin/products/categories')];
		if (productId) requests.push(api(`/admin/products/${productId}`));
		Promise.all(requests).then(([categoryData, productData]) => {
			setCategories(categoryData.categories);
			if (productData) {
				const product = productData.product;
				setForm({ category_id: String(product.category_id), name: product.name, description: product.description || '', price: product.price, stock_qty: String(product.stock_qty), low_stock_level: String(product.low_stock_level) });
			}
		}).catch((failure) => setError(failure.message)).finally(() => setLoading(false));
	}, [productId]);
	function update(event) { setForm({ ...form, [event.target.name]: event.target.value }); }
	async function submit(event) {
		event.preventDefault();
		setError('');
		setValidation({});
		setBusy(true);
		try {
			await api(productId ? `/admin/products/${productId}` : '/admin/products', { method: productId ? 'PUT' : 'POST', csrf: session.csrf, body: JSON.stringify(form) });
			navigate('/admin/products');
		} catch (failure) { setError(failure.message); setValidation(failure.validation || {}); }
		finally { setBusy(false); }
	}
	if (loading) return <p>Loading product…</p>;
	return <><span className="eyebrow">Shop management</span><h1 className="page-title">{productId ? 'Edit product' : 'Add product'}</h1><Notice error={error} /><form className="admin-product-form" onSubmit={submit}><label>Occasion<select name="category_id" value={form.category_id} onChange={update} required><option value="">Choose an occasion</option>{categories.map((category) => <option key={category.category_id} value={category.category_id}>{category.name}</option>)}</select><FieldError message={validation.category_id} /></label><label>Product name<input name="name" maxLength="120" value={form.name} onChange={update} required /><FieldError message={validation.name} /></label><label>Description<textarea name="description" maxLength="1000" rows="4" value={form.description} onChange={update} /><FieldError message={validation.description} /></label><label>Price (₱)<input name="price" inputMode="decimal" value={form.price} onChange={update} required /><FieldError message={validation.price} /></label><label>Stock quantity<input name="stock_qty" inputMode="numeric" value={form.stock_qty} onChange={update} required /><FieldError message={validation.stock_qty} /></label><label>Low-stock level<input name="low_stock_level" inputMode="numeric" value={form.low_stock_level} onChange={update} required /><FieldError message={validation.low_stock_level} /></label><div className="admin-form-actions"><button className="button" disabled={busy}>{busy ? 'Saving…' : productId ? 'Save changes' : 'Add product'}</button><Link className="arrow-link" to="/admin/products">Cancel</Link></div></form></>;
}

function AdminProductView({ productId, csrf }) {
	const [data, setData] = useState(null);
	const [error, setError] = useState('');
	useEffect(() => { api(`/admin/products/${productId}`).then(setData).catch((failure) => setError(failure.message)); }, [productId]);
	async function remove() {
		if (!window.confirm(`Delete "${data.product.name}"? This cannot be undone.`)) return;
		setError('');
		try { await api(`/admin/products/${productId}`, { method: 'DELETE', csrf }); window.location.assign('/admin/products'); }
		catch (failure) { setError(failure.message); }
	}
	if (!data) return <><Notice error={error} />{!error && <p>Loading product…</p>}</>;
	return <><span className="eyebrow">Product details</span><h1 className="page-title">{data.product.name}</h1><Notice error={error} /><p className="muted">{data.product.description}</p><div className="metric-grid"><article className="metric-card"><span>Stock</span><strong>{data.product.stock_qty}</strong></article><article className="metric-card"><span>Orders using product</span><strong>{data.usage.orders}</strong></article><article className="metric-card"><span>Stock records</span><strong>{data.usage.stock}</strong></article></div><div className="admin-form-actions"><Link className="button" to={`/admin/products/${productId}/edit`}>Edit product</Link>{data.can_delete && <button className="text-button" type="button" onClick={remove}>Delete product</button>}<Link className="arrow-link" to="/admin/products">← Back to products</Link></div></>;
}

function FieldError({ message }) {
	return message ? <small className="field-error">{message}</small> : null;
}

function DataTable({ rows, columns }) {
	if (!rows.length) return <p className="empty-state">No records to display.</p>;
	return <div className="table-wrap"><table><thead><tr>{columns.map(([label]) => <th key={label}>{label}</th>)}</tr></thead><tbody>{rows.map((row, index) => <tr key={row.order_id || row.product_id || row.user_id || row.delivery_id || index}>{columns.map(([label, key]) => <td key={key}>{row[key] ?? '—'}</td>)}</tr>)}</tbody></table></div>;
}

function App() {
	const [session, setSession] = useSession();
	if (!session.ready) return <div className="boot-screen"><span>✿</span><p>Preparing your flower shop…</p></div>;
	if (session.error) return <section className="boot-screen"><h1>Shop connection unavailable</h1><p>{session.error}</p><button className="button" type="button" onClick={() => window.location.reload()}>Try again</button></section>;
	return <BrowserRouter><Routes>
		<Route path="/admin/login" element={<SiteLayout session={session} setSession={setSession}><AuthPage mode="login" session={session} setSession={setSession} /></SiteLayout>} />
		<Route path="/admin/*" element={<AdminPage session={session} setSession={setSession} />} />
		<Route path="/staff/*" element={<AdminPage session={session} setSession={setSession} staffWorkspace />} />
		<Route path="/rider/*" element={<RiderPage session={session} setSession={setSession} />} />
		<Route path="/" element={<SiteLayout session={session} setSession={setSession}><HomePage /></SiteLayout>} />
		<Route path="/shop" element={<SiteLayout session={session} setSession={setSession}><ShopPage /></SiteLayout>} />
		<Route path="/shop/occasion/:slug" element={<SiteLayout session={session} setSession={setSession}><ShopPage /></SiteLayout>} />
		<Route path="/product/:id" element={<SiteLayout session={session} setSession={setSession}><ProductPage session={session} /></SiteLayout>} />
		<Route path="/login" element={<SiteLayout session={session} setSession={setSession}><AuthPage mode="login" session={session} setSession={setSession} /></SiteLayout>} />
		<Route path="/register" element={<SiteLayout session={session} setSession={setSession}><AuthPage mode="register" session={session} setSession={setSession} /></SiteLayout>} />
		<Route path="/account/orders" element={<SiteLayout session={session} setSession={setSession}><AccountOrdersPage session={session} /></SiteLayout>} />
		<Route path="/custom-orders" element={<SiteLayout session={session} setSession={setSession}><CustomOrdersPage session={session} /></SiteLayout>} />
		<Route path="/custom-orders/:id" element={<SiteLayout session={session} setSession={setSession}><CustomOrdersPage session={session} /></SiteLayout>} />
		<Route path="/track" element={<SiteLayout session={session} setSession={setSession}><TrackPage session={session} /></SiteLayout>} />
		<Route path="/about" element={<SiteLayout session={session} setSession={setSession}><AboutPage /></SiteLayout>} />
		<Route path="/cart" element={<SiteLayout session={session} setSession={setSession}><CartPage session={session} /></SiteLayout>} />
		<Route path="/checkout" element={<SiteLayout session={session} setSession={setSession}><CheckoutPage session={session} /></SiteLayout>} />
		<Route path="*" element={<SiteLayout session={session} setSession={setSession}><PlaceholderPage title="Page not found" session={session} /></SiteLayout>} />
	</Routes></BrowserRouter>;
}

createRoot(document.getElementById('react-root')).render(<React.StrictMode><App /></React.StrictMode>);
