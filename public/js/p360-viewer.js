/**
 * Produto 360 - Viewer
 * Adaptado do código base do usuário para suportar múltiplas instâncias na mesma página.
 *
 * Cada elemento .p360-viewer com data-p360-config será inicializado.
 * Config esperado:
 *   {
 *     images: [...],
 *     autoplay: bool,
 *     fps: number,
 *     direction: 1 | -1,
 *     color: '#hex'
 *   }
 */
(function () {
	'use strict';

	function P360Viewer(el, config) {
		this.el = el;
		this.config = config;
		this.img = el.querySelector('.p360-img');
		this.images = config.images || [];
		this.total = this.images.length;

		// estado
		this.index = 1;
		this.dragging = false;
		this.lastX = 0;
		this.acc = 0;
		this.pxPerFrame = 8;

		// sentido do arrasto (1 = natural, -1 = invertido)
		this.dragDirection = config.drag_direction === -1 ? -1 : 1;

		// zoom
		this.scale = 1;
		this.zoom = {
			min: 1,
			max: 3,
			stepBtn: 0.15,
			stepWheel: 0.10
		};
		this.initialScale = this.clampZoom(parseFloat(config.zoom_initial) || 1.0);

		// autoSpin
		this.autoSpin = {
			enabled: !!config.autoplay,
			fps: config.fps || 12,
			direction: config.direction === -1 ? -1 : 1,
			resumeAfterMs: 1800
		};
		this.spinTimer = null;
		this.resumeTimer = null;

		// touch
		this.touchMode = null;
		this.pinchStartDist = 0;
		this.pinchStartScale = 1;

		// preload
		this.cache = [];

		this.init();
	}

	P360Viewer.prototype.clampZoom = function (v) {
		if (isNaN(v)) return 1.0;
		if (v < 1.0) return 1.0;
		if (v > 3.0) return 3.0;
		return v;
	};

	P360Viewer.prototype.srcFor = function (i) {
		// 1-based
		return this.images[(i - 1 + this.total) % this.total];
	};

	P360Viewer.prototype.init = function () {
		var self = this;

		if (this.total < 2) {
			return;
		}

		// preload
		var loaded = 0;
		this.images.forEach(function (src) {
			var im = new Image();
			im.onload = im.onerror = function () {
				loaded++;
				if (loaded >= self.total) {
					self.onReady();
				}
			};
			im.src = src;
			self.cache.push(im);
		});

		this.render();
		this.bindEvents();
	};

	P360Viewer.prototype.onReady = function () {
		this.el.classList.add('is-ready');
		// Aplica zoom inicial configurado
		this.setScale(this.initialScale);
		var self = this;
		if (this.autoSpin.enabled) {
			setTimeout(function () { self.startSpin(); }, 600);
		}
	};

	P360Viewer.prototype.render = function () {
		this.img.src = this.srcFor(this.index);
	};

	P360Viewer.prototype.stepFrame = function (step) {
		this.index += step;
		if (this.index < 1) this.index = this.total;
		if (this.index > this.total) this.index = 1;
		this.render();
	};

	P360Viewer.prototype.setScale = function (s) {
		this.scale = Math.max(this.zoom.min, Math.min(this.zoom.max, s));
		this.img.style.transform = 'scale(' + this.scale + ')';
	};

	P360Viewer.prototype.stopSpin = function () {
		if (this.spinTimer) {
			clearInterval(this.spinTimer);
			this.spinTimer = null;
		}
	};

	P360Viewer.prototype.startSpin = function () {
		if (!this.autoSpin.enabled) return;
		if (this.dragging) return;
		if (this.spinTimer) return;
		var self = this;
		var interval = Math.max(16, Math.round(1000 / this.autoSpin.fps));
		this.spinTimer = setInterval(function () {
			self.stepFrame(self.autoSpin.direction);
		}, interval);
	};

	P360Viewer.prototype.scheduleResume = function () {
		this.stopSpin();
		if (this.resumeTimer) clearTimeout(this.resumeTimer);
		var self = this;
		this.resumeTimer = setTimeout(function () { self.startSpin(); }, this.autoSpin.resumeAfterMs);
	};

	P360Viewer.prototype.markInteracted = function () {
		this.el.classList.add('is-interacted');
	};

	P360Viewer.prototype.dragStepFromAcc = function (acc) {
		// dragDirection: 1 = natural (arrastar pra direita = gira pra direita)
		//                -1 = invertido (arrastar pra direita = gira pra esquerda)
		var natural = acc > 0 ? 1 : -1;
		return natural * this.dragDirection;
	};

	P360Viewer.prototype.onDown = function (x) {
		this.dragging = true;
		this.lastX = x;
		this.markInteracted();
		this.scheduleResume();
	};

	P360Viewer.prototype.onMove = function (x) {
		if (!this.dragging) return;
		var dx = x - this.lastX;
		this.lastX = x;
		this.acc += dx;

		while (Math.abs(this.acc) >= this.pxPerFrame) {
			var step = this.dragStepFromAcc(this.acc);
			this.stepFrame(step);
			this.acc += (this.acc > 0 ? -this.pxPerFrame : this.pxPerFrame);
		}
	};

	P360Viewer.prototype.onUp = function () {
		this.dragging = false;
		this.acc = 0;
		this.scheduleResume();
	};

	P360Viewer.prototype.dist = function (t1, t2) {
		var dx = t2.clientX - t1.clientX;
		var dy = t2.clientY - t1.clientY;
		return Math.hypot(dx, dy);
	};

	P360Viewer.prototype.bindEvents = function () {
		var self = this;
		var el = this.el;

		// mouse drag
		el.addEventListener('mousedown', function (e) {
			self.onDown(e.clientX);
		});
		window.addEventListener('mousemove', function (e) {
			self.onMove(e.clientX);
		});
		window.addEventListener('mouseup', function () {
			self.onUp();
		});

		// wheel zoom (desktop)
		el.addEventListener('wheel', function (e) {
			e.preventDefault();
			self.markInteracted();
			self.scheduleResume();
			var dir = e.deltaY > 0 ? -1 : 1;
			self.setScale(self.scale + dir * self.zoom.stepWheel);
		}, { passive: false });

		// zoom buttons
		var btns = el.querySelectorAll('.p360-btn');
		btns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				self.markInteracted();
				self.scheduleResume();
				var v = btn.getAttribute('data-z');
				if (v === '+') self.setScale(self.scale + self.zoom.stepBtn);
				if (v === '-') self.setScale(self.scale - self.zoom.stepBtn);
				if (v === 'reset') self.setScale(self.initialScale);
			});
		});

		// touch
		el.addEventListener('touchstart', function (e) {
			self.markInteracted();
			self.scheduleResume();

			if (e.touches.length === 1) {
				self.touchMode = 'drag';
				self.onDown(e.touches[0].clientX);
			} else if (e.touches.length === 2) {
				self.touchMode = 'pinch';
				self.dragging = false;
				self.acc = 0;
				self.pinchStartDist = self.dist(e.touches[0], e.touches[1]);
				self.pinchStartScale = self.scale;
			}
		}, { passive: false });

		el.addEventListener('touchmove', function (e) {
			if (self.touchMode === 'drag' && e.touches.length === 1) {
				self.onMove(e.touches[0].clientX);
			} else if (self.touchMode === 'pinch' && e.touches.length === 2) {
				e.preventDefault();
				var d = self.dist(e.touches[0], e.touches[1]);
				var factor = d / self.pinchStartDist;
				self.setScale(self.pinchStartScale * factor);
			}
		}, { passive: false });

		el.addEventListener('touchend', function () {
			self.touchMode = null;
			self.onUp();
		});
	};

	// === Auto-init ===
	function initAll() {
		var nodes = document.querySelectorAll('.p360-viewer[data-p360-config]');
		nodes.forEach(function (el) {
			if (el.dataset.p360Inited === '1') return;
			try {
				var config = JSON.parse(el.getAttribute('data-p360-config'));
				new P360Viewer(el, config);
				el.dataset.p360Inited = '1';
			} catch (err) {
				console.error('Produto 360: erro ao inicializar viewer', err);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	// Expor globalmente (útil para reinicializar dinamicamente, ex: AJAX, Elementor)
	window.P360Viewer = P360Viewer;
	window.P360InitAll = initAll;

})();
