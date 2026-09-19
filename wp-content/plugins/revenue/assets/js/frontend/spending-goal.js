/* global revenue_campaign Revenue jQuery wp */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */

( function ( $ ) {
	'use strict';

	// Utility functions grouped into a single object
	const Utils = {
		parsePx: ( value ) => parseFloat( value.replace( /px/, '' ) ),
		getRandomInRange: ( min, max, precision = 0 ) => {
			const multiplier = Math.pow( 10, precision );
			const randomValue = Math.random() * ( max - min ) + min;
			return Math.floor( randomValue * multiplier ) / multiplier;
		},
		getRandomItem: ( array ) =>
			array[ Math.floor( Math.random() * array.length ) ],
		getScaleFactor: () => Math.log( window.innerWidth ) / Math.log( 1920 ),
		debounce: ( func, delay ) => {
			let timeout;
			return ( ...args ) => {
				clearTimeout( timeout );
				timeout = setTimeout( () => func( ...args ), delay );
			};
		},
	};

	// Precomputed constants
	const DEG_TO_RAD = Math.PI / 180;

	// Centralized configuration for default values
	const defaultConfettiConfig = {
		confettiesNumber: 250,
		confettiRadius: 6,
		confettiColors: [
			'#fcf403',
			'#62fc03',
			'#f4fc03',
			'#03e7fc',
			'#03fca5',
			'#a503fc',
			'#fc03ad',
			'#fc03c2',
		],
		emojies: [],
		svgIcon: null,
	};

	// Confetti Classes
	class Confetti {
		constructor( {
			initialPosition,
			direction,
			radius,
			colors,
			emojis,
			svgIcon,
		} ) {
			const speedFactor =
				Utils.getRandomInRange( 0.9, 1.7, 3 ) * Utils.getScaleFactor();
			this.speed = { x: speedFactor, y: speedFactor };
			this.finalSpeedX = Utils.getRandomInRange( 0.2, 0.6, 3 );
			this.rotationSpeed =
				emojis.length || svgIcon
					? 0.01
					: Utils.getRandomInRange( 0.03, 0.07, 3 ) *
					  Utils.getScaleFactor();
			this.dragCoefficient = Utils.getRandomInRange( 0.0005, 0.0009, 6 );
			this.radius = { x: radius, y: radius };
			this.initialRadius = radius;
			this.rotationAngle =
				direction === 'left'
					? Utils.getRandomInRange( 0, 0.2, 3 )
					: Utils.getRandomInRange( -0.2, 0, 3 );
			this.emojiRotationAngle = Utils.getRandomInRange( 0, 2 * Math.PI );
			this.radiusYDirection = 'down';

			const angle =
				direction === 'left'
					? Utils.getRandomInRange( 82, 15 ) * DEG_TO_RAD
					: Utils.getRandomInRange( -15, -82 ) * DEG_TO_RAD;
			this.absCos = Math.abs( Math.cos( angle ) );
			this.absSin = Math.abs( Math.sin( angle ) );

			const offset = Utils.getRandomInRange( -150, 0 );
			const position = {
				x:
					initialPosition.x +
					( direction === 'left' ? -offset : offset ) * this.absCos,
				y: initialPosition.y - offset * this.absSin,
			};

			this.position = { ...position };
			this.initialPosition = { ...position };
			this.color =
				emojis.length || svgIcon ? null : Utils.getRandomItem( colors );
			this.emoji = emojis.length ? Utils.getRandomItem( emojis ) : null;
			this.svgIcon = null;
			this.createdAt = Date.now();
			this.direction = direction;

			if ( svgIcon ) {
				this.svgImage = new Image();
				this.svgImage.src = svgIcon;
				this.svgImage.onload = () => {
					this.svgIcon = this.svgImage;
				};
			}
		}

		draw( context ) {
			const { x, y } = this.position;
			const { x: radiusX, y: radiusY } = this.radius;
			const scale = window.devicePixelRatio;

			if ( this.svgIcon ) {
				context.save();
				context.translate( scale * x, scale * y );
				context.rotate( this.emojiRotationAngle );
				context.drawImage(
					this.svgIcon,
					-radiusX,
					-radiusY,
					radiusX * 2,
					radiusY * 2
				);
				context.restore();
			} else if ( this.color ) {
				context.fillStyle = this.color;
				context.beginPath();
				context.ellipse(
					x * scale,
					y * scale,
					radiusX * scale,
					radiusY * scale,
					this.rotationAngle,
					0,
					2 * Math.PI
				);
				context.fill();
			} else if ( this.emoji ) {
				context.font = `${ radiusX * scale }px serif`;
				context.save();
				context.translate( scale * x, scale * y );
				context.rotate( this.emojiRotationAngle );
				context.textAlign = 'center';
				context.fillText( this.emoji, 0, radiusY / 2 );
				context.restore();
			}
		}

		updatePosition( deltaTime, currentTime ) {
			const elapsed = currentTime - this.createdAt;

			if ( this.speed.x > this.finalSpeedX ) {
				this.speed.x -= this.dragCoefficient * deltaTime;
			}

			this.position.x +=
				this.speed.x *
				( this.direction === 'left' ? -this.absCos : this.absCos ) *
				deltaTime;
			this.position.y =
				this.initialPosition.y -
				this.speed.y * this.absSin * elapsed +
				( 0.00125 * Math.pow( elapsed, 2 ) ) / 2;

			if ( ! this.emoji && ! this.svgIcon ) {
				this.rotationSpeed -= 1e-5 * deltaTime;
				this.rotationSpeed = Math.max( this.rotationSpeed, 0 );

				if ( this.radiusYDirection === 'down' ) {
					this.radius.y -= deltaTime * this.rotationSpeed;
					if ( this.radius.y <= 0 ) {
						this.radius.y = 0;
						this.radiusYDirection = 'up';
					}
				} else {
					this.radius.y += deltaTime * this.rotationSpeed;
					if ( this.radius.y >= this.initialRadius ) {
						this.radius.y = this.initialRadius;
						this.radiusYDirection = 'down';
					}
				}
			}
		}

		isVisible( canvasHeight ) {
			return this.position.y < canvasHeight + 100;
		}
	}

	class ConfettiManager {
		constructor() {
			this.canvas = document.createElement( 'canvas' );
			this.canvas.style =
				'position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; pointer-events: none;';
			document.body.appendChild( this.canvas );
			this.context = this.canvas.getContext( '2d' );
			this.confetti = [];
			this.lastUpdated = Date.now();
			window.addEventListener(
				'resize',
				Utils.debounce( () => this.resizeCanvas(), 200 )
			);
			this.resizeCanvas();
			requestAnimationFrame( () => this.loop() );
		}

		resizeCanvas() {
			this.canvas.width = window.innerWidth * window.devicePixelRatio;
			this.canvas.height = window.innerHeight * window.devicePixelRatio;
		}

		addConfetti( config = {} ) {
			const {
				confettiesNumber,
				confettiRadius,
				confettiColors,
				emojies,
				svgIcon,
			} = {
				...defaultConfettiConfig,
				...config,
			};

			const baseY = ( 5 * window.innerHeight ) / 7;
			for ( let i = 0; i < confettiesNumber / 2; i++ ) {
				this.confetti.push(
					new Confetti( {
						initialPosition: { x: 0, y: baseY },
						direction: 'right',
						radius: confettiRadius,
						colors: confettiColors,
						emojis: emojies,
						svgIcon,
					} )
				);
				this.confetti.push(
					new Confetti( {
						initialPosition: { x: window.innerWidth, y: baseY },
						direction: 'left',
						radius: confettiRadius,
						colors: confettiColors,
						emojis: emojies,
						svgIcon,
					} )
				);
			}
		}

		resetAndStart( config = {} ) {
			this.confetti = [];
			this.addConfetti( config );
		}

		loop() {
			const currentTime = Date.now();
			const deltaTime = currentTime - this.lastUpdated;
			this.lastUpdated = currentTime;

			this.context.clearRect(
				0,
				0,
				this.canvas.width,
				this.canvas.height
			);

			this.confetti = this.confetti.filter( ( item ) => {
				item.updatePosition( deltaTime, currentTime );
				item.draw( this.context );
				return item.isVisible( this.canvas.height );
			} );

			requestAnimationFrame( () => this.loop() );
		}
	}

	// Main Progress Class
	class RevenueSpendingGoalProgress {
		constructor( options ) {
			this.defaults = {
				container: null,
				offersFieldName: 'revenue_spending_goal_offer',
				ajaxUrl: '',
				debugMode: false,
				refreshInterval: 10000,
				type: 'drawer', // 'drawer' or 'inpage'
			};

			this.settings = $.extend( {}, this.defaults, options ); // merges 2 object: options and defaults.
			this.container = $( this.settings.container ); // container is a jquery object of the wrapper passed in options
			this.state = {
				isOpen: false,
				currentProgress: 0,
				totalGoal: 0,
				activeOffers: [],
				unlockedRewards: [],
				cartTotal: 0,
				isUpdating: false,
				radius: this.container.data( 'radius' ),
				showConfetti: this.container.data( 'show-confetti' ),
			};

			this.confetti = new ConfettiManager();
			this.unsubscribeCartStore = null;
			this.initializeOffers();
		}

		initializeOffers() {
			try {
				const offersField = $(
					`input[name="${ this.settings.offersFieldName }"]`
				);
				this.state.cartTotal = this.container.data( 'cart-total' );

				if ( offersField.length ) {
					this.settings.offers = JSON.parse(
						offersField.val() || '[]'
					);
					this.init();
				} else {
					throw new Error( 'Offers hidden field not found' );
				}
			} catch ( error ) {
				console.error( 'Revenue X: Error parsing offers data:', error );
			}
		}

		init() {
			if ( ! this.validateSetup() ) {
				return;
			}

			this.cacheDOM();
			this.calculateTotalGoal();
			// works for classic carts where cart updates trigger wc events.
			this.bindEvents();
			// for block carts and checkouts.
			this.subscribeToCartTotal();
			this.initSliderIfNeeded();

			// Ensure the gift heading is correct on initial load
			this.updateGiftHeadingMessage( this.state.cartTotal );
		}

		validateSetup() {
			if (
				! this.container.length ||
				! this.settings.offers?.length ||
				! this.settings.ajaxUrl
			) {
				console.error( 'Revenue X: Invalid setup' );
				return false;
			}
			return true;
		}

		cacheDOM() {
			this.elements = {
				content: this.container.find(
					this.settings.type === 'drawer'
						? '.revx-drawer-content'
						: '.revx-progress-content'
				),
				progressBar: this.container.find(
					'.revx-progress-fill, .revx-stock-bar'
				),
				closeBtn: this.container.find( '.revx-close-btn' ),
				circularProgress: this.container.find(
					'.revx-progress-active'
				),
				circularText: this.container.find( '.revx-circular-text' ),
				message: this.container.find(
					'.revx-message, [data-smart-tag="spgHeading"]'
				),
				steps: this.container.find( '.revx-progress-step' ),
				rewardMessage: this.container.find(
					'.revx-spending-goal-reward-message'
				),
				finalMessage: this.container.data( 'final-message' ),
			};
		}

		calculateTotalGoal() {
			this.state.totalGoal = this.settings.offers.reduce(
				( sum, offer ) => sum + parseFloat( offer.spending_goal || 0 ),
				0
			);
		}

		// for block based cart price tracking.
		// subscribe to cart totals.
		// This is required as blocks cart updates do not trigger traditional wc events.
		// the subscribe handle the cart total change and trigger the progress update.
		subscribeToCartTotal() {
			if ( ! wp?.data?.select || ! wp?.data?.subscribe ) {
				return;
			}
			const { select, subscribe } = wp.data;

			let previousTotals = null;

			// woocommerce store subscribe to cart changes.
			this.unsubscribeCartStore = subscribe( () => {
				const cart = select( 'wc/store/cart' );

				if ( ! cart ) {
					return;
				}

				const totals = cart.getCartTotals?.();

				if ( totals?.total_price !== previousTotals?.total_price ) {
					previousTotals = totals;

					this.handleCartUpdate();
				}
			} );
		}

		bindEvents() {
			// Common events
			const cartEvents = [
				'updated_cart_totals',
				'added_to_cart',
				'removed_from_cart',
				'wc-blocks_added_to_cart',
				'wc-blocks_removed_from_cart',
			];
			$( document.body ).on( cartEvents.join( ' ' ), () =>
				this.handleCartUpdate()
			);

			// Toggle display for gift item remove buttons based on on-cart class
			this.toggleGiftItemDisplay();

			// Add to cart button events
			$( document ).on(
				'click',
				'.revx-spending-goal-add-cart',
				( e ) => {
					e.preventDefault();
					this.handleAddToCart( $( e.currentTarget ) );
				}
			);

			// Drawer-specific events
			if ( this.settings.type === 'drawer' ) {
				this.container
					.find( '.revx-circular-progress' )
					.on( 'click', () => this.toggleDrawer() );
				this.elements.closeBtn.on( 'click', ( e ) => {
					e.stopPropagation();
					this.closeDrawer();
				} );
				$( document ).on( 'click', ( e ) => {
					if (
						! $( e.target ).closest( this.settings.containerId )
							.length
					) {
						this.closeDrawer();
					}
				} );
			}

			$( document ).on( 'click', '.revx-gift-item-add', ( e ) => {
				this.addFreeGiftToCart( $( e.currentTarget ) );
			} );
			$( document ).on( 'click', '.revx-gift-item-remove', ( e ) => {
				this.removeGiftFromCart( $( e.currentTarget ) );
			} );
		}

		handleCartUpdate() {
			if ( $( this.container ).hasClass( 'hide' ) ) {
				$( this.container ).removeClass( 'hide' );
			}

			// window.location.reload();

			if ( this._cartUpdateTimeout ) {
				clearTimeout( this._cartUpdateTimeout );
			}
			this._cartUpdateTimeout = setTimeout(
				() => this.getCartTotal(),
				300
			);
		}

		toggleGiftItemDisplay( added_items_id = [], isRemove = false ) {
			// Toggle revx-active class on parent container based on on-cart status
			$( '.revx-spending-gift-action' ).each( function () {
				const $container = $( this );
				const $removeButton = $container.find(
					'.revx-gift-item-remove'
				);
				const $checkedButton = $container.find(
					'.revx-gift-item-checked'
				);

				const productId = $removeButton.data( 'product-id' );
				if (
					added_items_id.length > 0 &&
					productId &&
					added_items_id.includes( productId )
				) {
					// if not remove operation add class, otherwise remove class.
					$removeButton.toggleClass( 'on-cart', ! isRemove );
					$checkedButton.toggleClass( 'on-cart', ! isRemove );
				}
				// Check if either remove or checked button has 'on-cart' class
				const isOnCart =
					$removeButton.hasClass( 'on-cart' ) ||
					$checkedButton.hasClass( 'on-cart' );

				// Toggle revx-active class based on cart status
				$container.toggleClass( 'revx-active', isOnCart );
			} );
		}

		getCartTotal() {
			if ( this.state.isUpdating ) {
				return;
			}

			this.state.isUpdating = true;
			$.ajax( {
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: { action: 'revenue_get_cart_total' },
				success: ( response ) => {
					if ( response.success ) {
						const newCartTotal = parseFloat(
							response.data.subtotal
						);
						if ( newCartTotal !== this.state.cartTotal ) {
							this.state.cartTotal = newCartTotal;
							this.updateProgress( newCartTotal );
						}

						this.updateGiftHeadingMessage( newCartTotal );
					}
				},
				error: ( xhr, status, error ) => {
					console.error( 'WowRevenue: AJAX error:', error );
				},
				complete: () => {
					this.state.isUpdating = false;
				},
			} );
		}

		updateProgress( cartTotal ) {
			const stepWidth = 100 / this.settings.offers.length;
			let progress = 0;
			let remainingTotal = cartTotal;

			this.settings.offers.forEach( ( offer ) => {
				if ( ! offer.spending_goal ) {
					return;
				}

				const spendingGoal = parseFloat( offer.spending_goal );
				const contributionToProgress = Math.min(
					( stepWidth / spendingGoal ) *
						Math.min( spendingGoal, remainingTotal ),
					stepWidth
				);

				progress += contributionToProgress;
				remainingTotal -= Math.min( spendingGoal, remainingTotal );
			} );

			this.state.currentProgress = progress;
			this.elements.progressBar.css( 'width', `${ progress }%` );

			// Update circular progress if in drawer mode
			if ( this.settings.type === 'drawer' && this.state.radius ) {
				const circumference = 2 * Math.PI * this.state.radius;
				const offset =
					circumference - ( progress / 100 ) * circumference;
				this.elements.circularProgress
					.css( 'stroke-dasharray', circumference )
					.css( 'stroke-dashoffset', offset );

				this.elements.circularText.text(
					`${ progress.toFixed( 2 ) }%`
				);
			}

			this.updateStepStates( cartTotal );
			this.updateMessages( cartTotal );
		}

		updateStepStates( cartTotal ) {
			let accumulatedGoal = 0;
			let lastSuccessIcon = null;
			this.settings.offers.forEach( ( offer, index ) => {
				accumulatedGoal += parseFloat( offer.spending_goal );
				const stepElement = this.elements.steps.eq( index );
				const iconElements = stepElement.find(
					'.revx-step-icon-container, .revx-step-icon, .revx-progress-step-icon-container'
				);
				const isCompleted = cartTotal >= accumulatedGoal;

				if ( iconElements.hasClass( 'completed' ) ) {
					return;
				}

				iconElements.toggleClass( 'completed', isCompleted );

				if (
					isCompleted &&
					! this.state.unlockedRewards.includes( offer.key )
				) {
					iconElements
						.css(
							'background',
							'var(--revx-active-color, #F6F8FA)'
						)
						.find( 'svg' )
						.first()
						.parent()
						.css( 'color', 'var(--revx-inactive-color, #F6F8FA)' );

					lastSuccessIcon = iconElements;

					this.state.unlockedRewards.push( offer.key );
					this.triggerReward( offer );
				}
			} );
			if ( lastSuccessIcon ) {
				const msg = lastSuccessIcon
					.closest( '[data-success-message]' )
					.data( 'success-message' );
				// goal success container.
				const $gsc = lastSuccessIcon
					.closest( '[data-container-level="top"]' )
					.find( '.revx-spending-goal-success' );
				// $gsc.css( 'all', 'unset' );
				if ( msg ) {
					$gsc.addClass( 'revx-active' ).find( 'span' ).text( msg );
					setTimeout( () => {
						$gsc.removeClass( 'revx-active' );
					}, 3000 );
				}
			}
		}

		updateMessages( cartTotal ) {
			let accumulatedGoal = 0;
			let message = '';
			let rewardMessage = '';

			for ( const offer of this.settings.offers ) {
				if ( ! offer.spending_goal ) {
					continue;
				}

				accumulatedGoal += parseFloat( offer.spending_goal );
				const remainingAmount = Math.abs( accumulatedGoal - cartTotal );

				const rewardType = offer?.reward_type;
				let afterMessage = offer?.after_message ?? '';
				const discountValue = offer?.discount_value;

				switch ( rewardType ) {
					case 'discount': {
						const discountType = offer?.discount_type;
						if ( 'percentage' === discountType ) {
							afterMessage = afterMessage.replace(
								'{discount_value}',
								( offer?.discount_value ?? 0 ) + '%'
							);
						} else {
							afterMessage = afterMessage.replace(
								'{discount_value}',
								Revenue.formatPrice(
									offer?.discount_value ?? 0
								)
							);
						}

						break;
					}

					default:
						break;
				}

				const REWARD_TYPE_OPTIONS = {
					free_shipping: 'Free Shipping',
					discount: 'Discount',
					gift: 'Gift Items',
				};

				if ( cartTotal < accumulatedGoal ) {
					message =
						( offer.before_message || '' )
							.replace(
								'{remaining_amount}',
								Revenue.formatPrice(
									remainingAmount.toFixed( 2 )
								)
							)
							?.replace( '{reward_type}', rewardType )
							?.replace( '{discount_value}', discountValue ) ??
						'Before Message';

					// rewardMessage = offer.after_message || '';
					break;
				} else {
					afterMessage = ( afterMessage || '' ).replace(
						'{remaining_amount}',
						Revenue.formatPrice( remainingAmount.toFixed( 2 ) )
					);
					rewardMessage = afterMessage || '';
				}
			}

			if ( cartTotal >= accumulatedGoal ) {
				this.elements.message.text( this.elements.finalMessage );
			} else {
				this.elements.message.text( message );
			}

			if ( this.elements.rewardMessage.length ) {
				this.elements.rewardMessage.text( rewardMessage );
				if ( ! rewardMessage ) {
					this.elements.rewardMessage
						.parent()
						.addClass( 'no-message' );
				} else {
					this.elements.rewardMessage
						.parent()
						.removeClass( 'no-message' );
				}
			}
		}

		triggerReward( offer ) {
			$( document.body ).trigger( 'revx_reward_unlocked', [ offer ] );
			if ( this.state.showConfetti === 'yes' ) {
				this.confetti.addConfetti();
			}

			switch ( offer.reward_type ) {
				case 'free_shipping':
					$( document.body ).trigger( 'revx_free_shipping_unlocked', [
						offer,
					] );
					break;
				case 'discount':
					$( document.body ).trigger( 'revx_discount_unlocked', [
						{
							type: offer.discount_type,
							value: offer.discount_value,
							offer,
						},
					] );
					break;
				case 'gift':
					$( document.body ).trigger( 'revx_gift_unlocked', [
						{
							products: offer.gift_products,
							quantity: offer.gift_quantity,
							offer,
						},
					] );
					this.autoAddFreeGiftsToCart();
					break;
			}
		}

		updateGiftHeadingMessage( cartTotal ) {
			let accumulatedGoal = 0;
			let idx = -1;
			// iterate offers and update the first gift heading found (scoped to this container)
			for ( const offer of this.settings.offers ) {
				accumulatedGoal += parseFloat( offer.spending_goal || 0 );
				if ( offer?.reward_type !== 'gift' ) {
					continue;
				}
				++idx;

				const quantity = offer?.gift_quantity;
				const isAll = quantity === 'all';
				let message = '';
				// remaining needed amount (positive when customer still needs to spend more)
				const remainingAmount = accumulatedGoal - cartTotal;
				const isMultipleGifts = offer.gift_products.length >= 2;
				if ( accumulatedGoal > cartTotal ) {
					const formattedAmount = Revenue.formatPrice(
						Math.abs( remainingAmount ).toFixed( 2 )
					);

					if ( isAll ) {
						if ( isMultipleGifts ) {
							message = wp.i18n.sprintf(
								wp.i18n.__(
									'Spend %s more to claim your gifts!',
									'revenue'
								),
								formattedAmount
							);
						} else {
							message = wp.i18n.sprintf(
								wp.i18n.__(
									'Spend %s more to claim your gift!',
									'revenue'
								),
								formattedAmount
							);
						}
					} else {
						if ( quantity > 1 ) {
							message = wp.i18n.sprintf(
								wp.i18n.__(
									'Spend %1$s more to get any %2$d gift items!',
									'revenue'
								),
								formattedAmount,
								quantity
							);
						} else {
							message = wp.i18n.sprintf(
								wp.i18n.__(
									'Spend %1$s more to get any %2$d gift item!',
									'revenue'
								),
								formattedAmount,
								quantity
							);
						}
					}
				} else if ( isAll ) {
					if ( isMultipleGifts ) {
						message = wp.i18n.__(
							'Congrats! Your gifts are here!',
							'revenue'
						);
					} else {
						message = wp.i18n.__(
							'Congrats! Your gift is here!',
							'revenue'
						);
					}
				} else {
					if ( isMultipleGifts ) {
						message = wp.i18n.sprintf(
							wp.i18n.__(
								'Congrats! Choose any %d items.',
								'revenue'
							),
							quantity
						);
					} else {
						message = wp.i18n.sprintf(
							wp.i18n.__(
								'Congrats! Choose any %d item.',
								'revenue'
							),
							quantity
						);
					}
				}
				// Update the heading message scoped to this container and stop after first gift offer
				this.container
					.find( '.revx-spending-gift-heading' )
					.eq( idx )
					.html( message );
			}
		}

		handleAddToCart( $button ) {
			const productId = $button.data( 'product-id' );
			const campaignId = $button.data( 'campaign-id' );
			const quantity =
				$button
					.closest( '.revx-spending-goal-actions' )
					.find( 'input[data-name="revx_quantity"]' )
					.val() || 1;

			$button.addClass( 'loading' );

			$.ajax( {
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_add_to_cart',
					productId,
					campaignId,
					_wpnonce: revenue_campaign.nonce,
					quantity,
				},
				success: ( response ) => {
					Revenue.showToast(
						revenue_campaign?.added_to_cart || 'Added to cart'
					);

					$( document.body ).trigger( 'added_to_cart', [
						response?.data?.fragments,
						response?.data?.cart_hash,
						false,
					] );
				},
				complete: () => {
					$button.removeClass( 'loading' );
				},
			} );
		}

		autoAddFreeGiftsToCart() {
			$.ajax( {
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_auto_add_free_gifts',
					campaign_id: this.container.data( 'campaign-id' ),
				},
				success: ( response ) => {
					if ( response.success ) {
						if ( response.data.status ) {
							Revenue.showToast(
								revenue_campaign?.free_gifts_added_to_cart ||
									'Free gifts added to cart'
							);

							// Update gift item display after adding to cart
							const added_items_id = response.data.added_items_id;
							this.toggleGiftItemDisplay( added_items_id );

							[
								'wc_fragment_refresh',
								'update_checkout',
								'wc_update_cart',
							].forEach( function ( evt ) {
								$( document.body ).trigger( evt );
							} );
						}
						// else {
						// 	Revenue.showToast( response.data.message, 'error' );
						// }
					}
				},
				error: ( response ) => {
					console.log( response );
				},
			} );
		}
		addFreeGiftToCart( $button ) {
			const productId = $button.data( 'product-id' );

			$.ajax( {
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_add_free_gift',
					campaign_id: this.container.data( 'campaign-id' ),
					product_id: productId,
				},
				success: ( response ) => {
					if ( response.success ) {
						if ( response.data.status ) {
							Revenue.showToast(
								revenue_campaign?.free_gift_added_to_cart ||
									'Free gift added to cart'
							);
							[
								'wc_fragment_refresh',
								'update_checkout',
								'wc_update_cart',
							].forEach( function ( evt ) {
								$( document.body ).trigger( evt );
							} );
							// Update gift item display after adding to cart
							// pass array of prouductid to modify the button option
							this.toggleGiftItemDisplay( [ productId ] );
						} else {
							Revenue.showToast( response.data.message, 'error' );
						}
					}
				},
			} );
		}
		removeGiftFromCart( $button ) {
			const productId = $button.data( 'product-id' );

			$.ajax( {
				url: this.settings.ajaxUrl,
				type: 'POST',
				data: {
					action: 'revenue_remove_free_gift',
					campaign_id: this.container.data( 'campaign-id' ),
					product_id: productId,
				},
				success: ( response ) => {
					if ( response.success && response.data.status ) {
						Revenue.showToast(
							revenue_campaign?.free_gift_removed ||
								'Free gift removed',
							'warning'
						);
						// Update mini cart, cart and checkout sequentially.
						// add more events as required.
						[
							'wc_fragment_refresh',
							'wc_update_cart',
							'update_checkout',
						].forEach( function ( evt ) {
							$( document.body ).trigger( evt );
						} );
						// Update gift item display after removing from cart.
						// pass array of product id to update button for specific gift.
						// pass true when this is remove operation.
						this.toggleGiftItemDisplay( [ productId ], true );
					}
				},
			} );
		}

		initSliderIfNeeded() {
			const upsellProducts = JSON.parse(
				$( 'input[name="revenue_upsell_products"]' ).val() || '[]'
			);

			if ( upsellProducts.length > 0 ) {
				this.initSlider();
			}
		}

		initSlider() {
			const $slider = $( '.revx-spending-goal-slider' );
			const $track = $slider.find( '.revx-spending-goal-slider-track' );
			const $items = $track.find( '.revx-spending-goal-product-card' );
			let currentIndex = 0;
			const totalItems = $items.length;

			// Clone items for infinite scroll
			const $firstClone = $items.first().clone().addClass( 'clone' );
			const $lastClone = $items.last().clone().addClass( 'clone' );
			$track.append( $firstClone );
			$track.prepend( $lastClone );

			const updateSlider = ( animate = false ) => {
				const itemWidth = $items.first().outerWidth( true );

				const translateX = -( currentIndex + 1 ) * itemWidth;

				$track.css(
					'transition',
					animate ? 'transform 0.3s ease-in-out' : 'none'
				);
				$track.css( 'transform', `translateX(${ translateX }px)` );

				if ( currentIndex === -1 ) {
					setTimeout( () => {
						$track.css( 'transition', 'none' );
						currentIndex = totalItems - 1;
						const finalTranslateX =
							-( currentIndex + 1 ) * itemWidth;
						$track.css(
							'transform',
							`translateX(${ finalTranslateX }px)`
						);
					}, 300 );
				} else if ( currentIndex === totalItems ) {
					setTimeout( () => {
						$track.css( 'transition', 'none' );
						currentIndex = 0;
						const finalTranslateX = -itemWidth;
						$track.css(
							'transform',
							`translateX(${ finalTranslateX }px)`
						);
					}, 300 );
				}
			};

			// Navigation
			$( '.revx-spending-goal-prev' ).on( 'click', () => {
				currentIndex = ( currentIndex - 1 + totalItems ) % totalItems;
				updateSlider( true );
			} );

			$( '.revx-spending-goal-next' ).on( 'click', () => {
				currentIndex = ( currentIndex + 1 ) % totalItems;
				updateSlider( true );
			} );

			// Touch events
			let touchStartX = 0;
			let touchEndX = 0;

			$slider.on( 'touchstart', ( e ) => {
				touchStartX = e.touches[ 0 ].clientX;
			} );

			$slider.on( 'touchmove', ( e ) => {
				touchEndX = e.touches[ 0 ].clientX;
			} );

			$slider.on( 'touchend', () => {
				const swipeThreshold = 50;
				const swipeDistance = touchEndX - touchStartX;

				if ( Math.abs( swipeDistance ) > swipeThreshold ) {
					if ( swipeDistance > 0 ) {
						$( '.revx-spending-goal-prev' ).trigger( 'click' );
					} else {
						$( '.revx-spending-goal-next' ).trigger( 'click' );
					}
				}
			} );

			// Initial setup
			updateSlider();
			// if (this.state.isOpen) {
			// 	updateSlider();
			// }
			$( window ).on( 'load resize', () => updateSlider() );
		}

		// Drawer-specific methods
		toggleDrawer() {
			if ( this.settings.type !== 'drawer' ) {
				return;
			}

			this.state.isOpen = ! this.state.isOpen;
			this.elements.content.toggleClass( 'open', this.state.isOpen );

			if ( this.state.isOpen ) {
				this.updateProgress( this.state.cartTotal );
				this.initSlider();
			}
		}

		closeDrawer() {
			if ( this.settings.type !== 'drawer' ) {
				return;
			}

			this.state.isOpen = false;
			this.elements.content.removeClass( 'open' );
		}

		destroy() {
			if ( this._cartUpdateTimeout ) {
				clearTimeout( this._cartUpdateTimeout );
			}

			this.container.find( '.revx-circular-progress' ).off();
			this.elements.closeBtn.off();
			$( document ).off( 'click.revenueX' );
			$( document.body ).off(
				'added_to_cart.revenueX removed_from_cart.revenueX updated_cart_totals.revenueX'
			);
			$( '.revx-spending-goal-add-cart' ).off();

			if ( this.unsubscribeCartStore ) {
				this.unsubscribeCartStore();
			}
		}
	}

	// Plugin registration
	$.fn.revenueSpendingGoalProgress = function ( options ) {
		return this.each( function () {
			if ( ! $.data( this, 'revenueSpendingGoalProgress' ) ) {
				$.data(
					this,
					'revenueSpendingGoalProgress',
					new RevenueSpendingGoalProgress( options )
				);
			}
		} );
	};

	function init() {
		// Initialize spending goals if present. only select the top level container of the speding goal
		$(
			'.revx-template [data-campaign-type="spending_goal"][data-container-level="top"]'
		).each( function () {
			$( this ).revenueSpendingGoalProgress( {
				ajaxUrl: revenue_campaign.ajax,
				container: $( this ),
				type: $( this ).data( 'position' ), // drawer or inpage now
			} );

			// After initialization(page-load/add-to-cart through form submit), call autoAddFreeGiftsToCart
			// so the instance adds any claimable free gift available in the spending goal from backend via ajax.
			const inst = $.data( this, 'revenueSpendingGoalProgress' );
			if ( inst && typeof inst.autoAddFreeGiftsToCart === 'function' ) {
				inst.autoAddFreeGiftsToCart();
			}
		} );
	}

	// Initialize the plugin
	$( document ).ready( function () {
		init();
		// tried to fix the issue of after cart reload
		// Re-run every time checkout updates (AJAX reload)
		$( document ).on( 'updated_checkout', function () {
			init();
		} );
	} );
} )( jQuery );
