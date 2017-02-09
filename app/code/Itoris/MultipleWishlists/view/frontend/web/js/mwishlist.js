/**
 * Copyright © 2016 ITORIS INC. All rights reserved.
 * See license agreement for details
 */
 
window.itorisMultipleWishlists = {
	popupShowEffectSpeed: 300, //ms
	popupHideEffectSpeed: 300, //ms
    tabAccordionSpeed: 300, //ms
    mobileViewWidth: 750, //px
	tabCache: [],

	initialize: function(wishlists, config){
		var skipEvents = this.wishlists ? true : false;
        this.config = config;
		this.wishlists = wishlists;
		this.popupContainer = jQuery('.mwishlist');
		this.popupBox = jQuery('.mwishlist .mwishlist-popup');
		jQuery('#mwishlist_popup_cancel').on('click', function(){window.itorisMultipleWishlists.hidePopup();});
		jQuery('#mwishlist_popup_add').on('click', function(){window.itorisMultipleWishlists.addToWishlist();})
		var wishlistNew = jQuery('#mwishlist_new');
        if (wishlistNew[0]) {
            wishlistNew[0].value = wishlistNew.attr('title');
            wishlistNew.addClass('empty');
            jQuery('.mwishlist_popup_body .mwishlist_row input[type=radio]').on('click', function(){
                if (this.value-0 == -1) {
                    wishlistNew.removeAttr('disabled');
                } else {
                    wishlistNew.attr({disabled: 'disabled'}).val(wishlistNew.addClass('empty').attr('title'));
                }
            });
            wishlistNew.on({
                focus: function(){
                    if (wishlistNew.val() == wishlistNew.attr('title')) wishlistNew.removeClass('empty').val('');
                },
                blur: function(){
                    if (wishlistNew.val() == '') wishlistNew.val(wishlistNew.addClass('empty').attr('title'));
                }
            });
        }
		if (!skipEvents) {
			this.mask = jQuery('<div>').addClass('mwishlist_mask').css({display: 'none'});
			this.mask.on('click', function(){window.itorisMultipleWishlists.hidePopup();});
			this.mask.insertBefore(this.popupContainer);
			this.loader = jQuery('<div>').addClass('mwishlist_loading').css({display: 'none'});
			this.loader.insertBefore(this.popupContainer);
			this.closePopup = jQuery('<div>').addClass('mwishlist_popup_close');
			this.closePopup.on('click', function(){window.itorisMultipleWishlists.hidePopup();});
			this.popupContainer.append(this.closePopup);
			this.popupMask = jQuery('<div>').addClass('mwishlist_popup_mask').hide();
			this.popupLoader = jQuery('<div>').addClass('mwishlist_popup_loading').hide();
			this.popupContainer.append(this.popupMask).append(this.popupLoader);
			this.attachLinkEvents();
			jQuery(window).on('resize', function(){window.itorisMultipleWishlists.resize();});
            if (jQuery('body.wishlist-index-index')[0]) this.initTabs();
		}
        if (!this.wishlistsUpdated) this.getWishlistUpdate();
	},
	
    getWishlistUpdate: function() {
        var _this = this;        
        _this.wishlistsUpdated = true;
        _this.popupMask.show();
		_this.popupLoader.show();		
		jQuery.ajax({
			type: 'GET',
			url: _this.config.getWishlistsUrl,
			dataType: 'json',
			success: function(data){
				_this.popupMask.hide();
				_this.popupLoader.hide();
				if (data.success) {
                    _this.popupBox.html(data.popup_html);
                    _this.wishlists = data.wishlists;
                    _this_config = data.config;
				}
			}
		});
        _this.updateTotal();
    },
    
    initTabs: function(){
        var form = jQuery('#wishlist-view-form'), _this = this;
        if (form[0]) {
            this.createTabs();
            this.resize();
        } else setTimeout(function(){_this.initTabs()}, 200);
    },
    
	attachLinkEvents: function() {
		var _this = this;
		jQuery(window).on('mousedown', function(ev) {
			var link = jQuery(ev.target);
			if (!link.is('a')) link = link.closest('a');
			if (link[0] && (link[0].mwishlistEventAttached == 'add_to_wishlist' || link.attr('data-post') && link.attr('data-post').indexOf('wishlist\\/index\\/add') > -1)) {
				if (!link[0].mwishlistEventAttached) {
					link.attr({'orig-data-post': link.attr('data-post'), 'href': 'javascript://'});
					link.removeAttr('data-post');
					link[0].mwishlistEventAttached = 'add_to_wishlist';
				}
				if (_this.activeContainer) _this.activeContainer.removeClass('container-visible');
				_this.activeLink = link;
				_this.activeContainer = _this.popupBox;
				_this.showPopup({makeNativePost: !_this.config.afterWishlistSelected});
			}
			if (link[0] && (link[0].mwishlistEventAttached == 'add_to_wishlist_from_cart' || link.attr('data-post') && link.attr('data-post').indexOf('wishlist\\/index\\/fromcart') > -1)) {
				if (!link[0].mwishlistEventAttached) {
					link.attr({'orig-data-post': link.attr('data-post'), 'href': 'javascript://'});
					link.removeAttr('data-post');
					link[0].mwishlistEventAttached = 'add_to_wishlist_from_cart';
				}
				if (_this.activeContainer) _this.activeContainer.removeClass('container-visible');
				_this.activeLink = link;
				_this.activeContainer = _this.popupBox;
				_this.showPopup({makeNativePost: true});
			}
			if (link[0] && (link[0].mwishlistEventAttached == 'update_wishlist' || link.attr('data-post') && link.attr('data-post').indexOf('wishlist\\/index\\/updateItemOptions') > -1)) {
				if (!link[0].mwishlistEventAttached) {
					link.attr({'orig-data-post': link.attr('data-post'), 'href': 'javascript://'});
					link.removeAttr('data-post').removeAttr('data-action');
					link[0].mwishlistEventAttached = 'update_wishlist';
				}
				_this.updateItemOptions(link);
			}			
			if (link[0] && (link[0].mwishlistEventAttached == 'remove_item' || link.attr('data-post-remove') && link.attr('data-post-remove').indexOf('wishlist\\/index\\/remove') > -1)) {
				if (!link[0].mwishlistEventAttached) {
					link.attr({'orig-data-post-remove': link.attr('data-post-remove'), 'href': 'javascript://'});
					link.removeAttr('data-post-remove').removeAttr('data-role');
					link[0].mwishlistEventAttached = 'remove_item';
				}
				_this.removeItem(link);
			}
            
            var button = jQuery(ev.target);
            if (!button.is('button')) button = button.closest('button');
			if (button[0] && (button[0].mwishlistEventAttached == 'add_to_cart' || button.attr('data-post') && button.attr('data-post').indexOf('wishlist\\/index\\/cart') > -1 && button.closest('#wishlist-view-form')[0])) {
                if (!button[0].mwishlistEventAttached) {
					button.removeAttr('data-post');
					button[0].mwishlistEventAttached = 'add_to_cart';
				}
				_this.addToCart(ev);
			}
			if (button[0] && button.hasClass('share')) {
				_this.shareWishlist(ev);
			}
			if (button[0] && button.hasClass('update') && button.closest('#wishlist-view-form')[0]) {
				_this.updateWishlist(ev);
			}
			if (button[0] && button.hasClass('all_to_cart')) {
				_this.addAllToCart(ev);
			}
            
		});
        var allToCartBtn = jQuery('#wishlist-view-form [data-role="all-tocart"]').removeAttr('data-role').addClass('all_to_cart');
	},
	
	addToWishlist: function() {
		var postData = JSON.parse(this.activeLink.attr('orig-data-post')), _this = this;
		postData.data.mwishlist_id = jQuery('input[name="mwishlist_id"]:checked').val();
		if (postData.data.mwishlist_id-0 == -1 && jQuery('#mwishlist_new').hasClass('empty')) {
			alert('Please enter new wishlist name');
			jQuery('#mwishlist_new')[0].focus();
			return;
		}
		postData.data.mwishlist_name = jQuery('#mwishlist_new')[0].value;
		
		if (!this.config.isLoggedIn || _this.popupOptions && _this.popupOptions.makeNativePost) {
			this.hidePopup();
			this.activeLink.attr('data-post', JSON.stringify(postData));
			this.activeLink.click();
			return;
		}
		
		var form = jQuery("#product_addtocart_form"), swatch = this.activeLink.closest('.product-item');
		if (form[0]) {
			form.serializeArray().map(function(x){postData.data[x.name] = x.value;});
		} else if (swatch[0]) {
			swatch.find('.swatch-attribute').each(function(index, attribute){
				var attributeId = jQuery(attribute).attr('attribute-id');
				var option = jQuery(attribute).find('.swatch-option.selected');
				if (option[0]) postData.data['super_attribute['+attributeId+']'] = jQuery(option).attr('option-id');
			});
		}

		postData.data.mwishlist_ajax = 1;
        if (!postData.data.form_key && jQuery('input[name="form_key"]')[0]) postData.data.form_key = jQuery('input[name="form_key"]')[0].value;
		_this.popupMask.show();
		_this.popupLoader.show();
		
		jQuery.ajax({
			type: 'POST',
			url: postData.action,
			data: postData.data,
			dataType: 'json',
			success: function(data){
				_this.popupMask.hide();
				_this.popupLoader.hide();
				if (data.error) {
					alert(data.error);
				} else if (data.success) {
					_this.popupBox._html = data.popup_html;
					_this.popupBox.html('<div class="message success"><div></div></div>');
					_this.popupBox.find('.message > div').text(data.message);
					_this.resize();
					if (_this.popupOptions && _this.popupOptions.complete) _this.popupOptions.complete(data);
				} else {
					alert('Error occurred while adding product to wishlist');
				}
			}
		});
	},
	
	resize: function(){
		if (this.activeContainer) {
			var size = {width: this.popupContainer.width(), height: this.popupContainer.height()};
			var top = (jQuery(window).height() - size.height) / 2 + jQuery(window).scrollTop() - 5, top = top < 10 ? 10 : top;
			var left = (jQuery(window).width() - size.width) / 2 + jQuery(window).scrollLeft() - 5, left = left < 0 ? 0 : left;
			this.popupContainer.css({left: left, top: top});
		}
		if (this.tabContainer) {
            if (this.tabContainer.width() > this.mobileViewWidth) {
                if (this.tabContainer.hasClass('mwishlist-mobile')) {
                    this.tabContainer.removeClass('mwishlist-mobile');
                    jQuery('#mwishlist-tabs-body').insertAfter(jQuery('#mwishlist-tabs'));
                }
                jQuery([0, 50, 550, 850]).each(function(index, delay){setTimeout(function(){window.itorisMultipleWishlists.updateTabArrows()}, delay);});
            } else {
                if (!this.tabContainer.hasClass('mwishlist-mobile')) {
                    this.tabContainer.addClass('mwishlist-mobile');
                    jQuery('#mwishlist-tabs-body').insertAfter(jQuery('.mwishlist-active-tab'));
                }
            }
        }
	},
	
	showPopup: function(options){
		this.popupOptions = options ? options : {}
		if (this.popupBox._html) {
			this.popupBox.html(this.popupBox._html);
			this.popupBox._html = false;
		}
		this.mask.addClass('black').css({opacity: 0, display: 'block'});
		var offset = this.activeLink.offset();
		if (!this.activeLink.is(':visible')) {
			offset = {left: jQuery(window).width() / 2 + jQuery(window).scrollLeft(), top: jQuery(window).height() / 2 + jQuery(window).scrollTop()}
		}
		this.activeLink._offset = offset;
		this.activeContainer.addClass('container-visible');

		this.popupContainer[0].style.cssText = 'width:auto; height:auto;';
		var size = {width: this.popupContainer.width(), height: this.popupContainer.height()};
		this.activeContainer.css({zoom: 0});
		this.popupContainer.addClass('wborder').css({
			'-moz-transform': 'scale(0)',
			'-o-transform': 'scale(0)',
			'-webkit-transform': 'scale(0)',
			transform: 'scale(0)',
			'-moz-transform-origin': '0 0',
			'-o-transform-origin': '0 0',
			'-webkit-transform-origin': '0 0',
			'transform-origin': '0 0',
			left: offset.left,
			top: offset.top,
			opacity: 0
		});
		var top = (jQuery(window).height() - size.height) / 2 + jQuery(window).scrollTop() - 5, top = top < 10 ? 10 : top;
		var left = (jQuery(window).width() - size.width) / 2 + jQuery(window).scrollLeft() - 5, left = left < 0 ? 0 : left;
		this.popupContainer.animate({ left: left, top: top }, {
			easing: 'linear',
			duration: this.popupShowEffectSpeed,
			progress: function(a, p, r){
				window.itorisMultipleWishlists.popupContainer.css({
					'-moz-transform': 'scale('+p+')',
					'-o-transform': 'scale('+p+')',
					'-webkit-transform': 'scale('+p+')',
					transform: 'scale('+p+')',
					opacity: p
				});
				window.itorisMultipleWishlists.activeContainer.css({zoom: p});
				window.itorisMultipleWishlists.mask.css({opacity: p*0.7});
			}
		});
		
		var header = jQuery('.mwishlist_popup_header'), button = jQuery('#mwishlist_popup_add');
		if (!header[0]._origText) header[0]._origText = header.html();
		if (!button[0]._origText) button[0]._origText = button.html();
		header.html(this.popupOptions.headerText ? this.popupOptions.headerText : header[0]._origText);
		button.html(this.popupOptions.buttonText ? this.popupOptions.buttonText : button[0]._origText);
	},

	hidePopup: function() {
		if (this.popupLoader.is(':visible')) return;
		var offset = this.activeLink._offset, _this = this;
		if (!this.activeLink._offset) {
			offset = {left: jQuery(window).width() / 2 + jQuery(window).scrollLeft(), top: jQuery(window).height() / 2 + jQuery(window).scrollTop()}
		}
		this.popupContainer.animate({
			left: offset.left,
			top: offset.top
		}, {
			duration: this.popupHideEffectSpeed,
			progress: function(a, p, r){
				_this.popupContainer.css({
					'-moz-transform': 'scale('+(1-p)+')',
					'-o-transform': 'scale('+(1-p)+')',
					'-webkit-transform': 'scale('+(1-p)+')',
					transform: 'scale('+(1-p)+')',
					opacity: 1-p
				});

				if(_this.activeContainer != false){
					_this.activeContainer.css({zoom: 1-p});
					_this.mask.css({opacity: (0.7-p*0.7)});
				}
			},
			complete: function(){
				if (_this.activeContainer != false){
					_this.activeContainer.removeClass('container-visible');
					_this.popupContainer.removeClass('wborder');
					_this.mask.css({display: 'none', opacity: 0.7});
					_this.activeContainer = false;
				}
                if (_this.popupBox._html) {
                    _this.popupBox.html(_this.popupBox._html);
                    _this.popupBox._html = false;
                }
			}
		});
		this.popupOptions = false;
	},
	
	createTabs: function(){
		var form = jQuery('#wishlist-view-form'), _this = this;
		if (!form[0]) return;
		this.actionToolbar = form.find('.actions-toolbar');
		if (!jQuery('.mwishlist-item')[0]) this.actionToolbar.detach();
		this.tabContainer = jQuery('<div />', {id: 'mwishlist-tab-container'}).html(
			'<div id="mwishlist-tab-create"><input type="text" id="mwishlist-create" /><button class="action primary" type="button"><span>Create Wishlist</span></button></div>'+
			'<div id="mwishlist-tabs"><div id="mwishlist-tabs-outer"><div id="mwishlist-tabs-inner"></div></div>'+
			'<div id="mwishlist-left-arrow"></div><div id="mwishlist-right-arrow"></div></div><div id="mwishlist-tabs-body"></div>'
		);
		form.before(this.tabContainer);
		
		var wishlistCreate = jQuery('#mwishlist-create');
		wishlistCreate.attr({title: 'Enter a new wishlist name here'});		
		wishlistCreate[0].value = wishlistCreate.attr('title');
		wishlistCreate.addClass('empty');
		wishlistCreate.on({
			focus: function(){
				if (wishlistCreate.val() == wishlistCreate.attr('title')) wishlistCreate.removeClass('empty').val('');
			},
			blur: function(){
				if (wishlistCreate.val() == '') wishlistCreate.val(wishlistCreate.addClass('empty').attr('title'));
			},
			keypress: function(ev) {
				if (ev.keyCode == 13) _this.createWishlist();
			}
		});	
		jQuery('#mwishlist-tab-create button.primary').on('click', this.createWishlist);
		jQuery('#mwishlist-tabs-body').append(form);
		jQuery.each(this.wishlists, this.createTab.bind(this));
		jQuery('#mwishlist-left-arrow').on('click', function(ev){
			var inner = jQuery('#mwishlist-tabs-inner'), tabs = jQuery('.mwishlist-tab'), left = 0;
			for(var i=tabs.length-1; i>1; i--) if (tabs[i].offsetLeft + inner[0].offsetLeft <= 0) break;
			if (i > 1) left = - (tabs[i-2].offsetLeft + tabs[i-2].offsetWidth) - 20;
			inner.css({left: left + 'px'});
			jQuery([0, 50, 550, 850]).each(function(index, delay){setTimeout(function(){_this.updateTabArrows()}, delay);});
		});
		jQuery('#mwishlist-right-arrow').on('click', function(ev){
			var inner = jQuery('#mwishlist-tabs-inner'), tabs = jQuery('.mwishlist-tab');
			for(var i=tabs.length-1; i>0; i--) if (tabs[i].offsetLeft + inner[0].offsetLeft <= 0) break;
			inner.css({left: - (tabs[i].offsetLeft + tabs[i].offsetWidth) - 20 + 'px'});
			jQuery([0, 50, 550, 850]).each(function(index, delay){setTimeout(function(){_this.updateTabArrows()}, delay);});
		});
		this.updateTabArrows();
	},
	
	createTab: function(index, wishlist){
		var tab = jQuery('<div />', {'class': 'mwishlist-tab'+(index == 0 ? ' mwishlist-active-tab' : '')}).html(
			'<span class="mwishlist-tab-title"></span><span class="mwishlist-tab-qty"></span>'
		);
		tab[0].tabname = index > 0 ? wishlist.name : '';
		tab[0].tabid = index > 0 ? wishlist.id : 0;
		jQuery('#mwishlist-tabs-inner').append(tab);
		tab.find('.mwishlist-tab-title').text(wishlist.name);
		tab.find('.mwishlist-tab-qty').text('('+wishlist.qty+')');
		tab.on('click', function(ev){window.itorisMultipleWishlists.switchTab(jQuery(this), true);});
		if (wishlist.name == this.getUrlParams().mwishlist) this.switchTab(tab, false);
		return tab;
	},
	
	updateTabArrows: function(){
		var inner = jQuery('#mwishlist-tabs-inner')[0], outer = jQuery('#mwishlist-tabs-outer')[0], tabs = jQuery('#mwishlist-tabs');
		if (inner.offsetWidth + inner.offsetLeft > outer.offsetWidth) tabs.addClass('with-right-arrow'); else tabs.removeClass('with-right-arrow');
		if (inner.offsetWidth + inner.offsetLeft < outer.offsetWidth && inner.offsetLeft < 0) jQuery(inner).css({left: outer.offsetWidth - inner.offsetWidth + 'px'});
		if (inner.offsetLeft < 0) tabs.addClass('with-left-arrow'); else tabs.removeClass('with-left-arrow');
		if (inner.offsetLeft > 0) jQuery(inner).css({left: '0px'});
	},
	
	switchTab: function(tab, update) {
        if (tab.hasClass('mwishlist-active-tab')) return;
		if (jQuery('.mwishlist-active-tab') && update) this.tabCache[jQuery('.mwishlist-active-tab')[0].tabid] = jQuery('#mwishlist-tabs-body').html();
		jQuery('.mwishlist-active-tab').removeClass('mwishlist-active-tab');
		tab.addClass('mwishlist-active-tab');
        var _this = this;
		if (update) {
			var url = document.location.href, pos = url.indexOf('?'), base = pos > -1 ? url.substr(0, pos) : url;
			window.history.pushState({}, null, tab[0].tabname ? '?mwishlist='+escape(tab[0].tabname) : base);
			var getUrl = document.location.href, getUrl = getUrl + (getUrl.indexOf('?') > -1 ? '&' : '?') + 'isAjax=1';
            var bodyContent = jQuery('#mwishlist-tabs-body').html();
			if (this.tabCache[tab[0].tabid]) {
				jQuery('#mwishlist-tabs-body').html(this.tabCache[tab[0].tabid]);
                this.switchTabMobileHelper(bodyContent);
				return;
			}
			this.mask.removeClass('black').show();
			this.loader.show();
			jQuery.ajax({
				type: 'POST',
				url: getUrl,
				data: {
                    mwishlist_id: tab[0].tabid,
                    form_key: jQuery('input[name="form_key"]')[0] ? jQuery('input[name="form_key"]')[0].value : ''
                },
				success: function(data){
                    if (data.indexOf('<body') > -1) data = jQuery('#mwishlist-tabs-body').html();
					jQuery('#mwishlist-tabs-body').html(data);
                    _this.switchTabMobileHelper(bodyContent);
					if (jQuery('#wishlist-view-form .mwishlist-item')[0]) {
						jQuery('#wishlist-view-form').append(_this.actionToolbar);
					}
					_this.mask.hide();
					_this.loader.hide();
                    _this.updateTotal();
				}
			});
		} else if (!jQuery('#wishlist-view-form .mwishlist-item')[0]) {
			_this.actionToolbar.detach();
		}
	},
    
    switchTabMobileHelper: function(bodyContent){
        if (!this.tabContainer.hasClass('mwishlist-mobile')) return;
        var body = jQuery('#mwishlist-tabs-body'), bodyTemp = body.clone().html(bodyContent).insertAfter( body ), activeTab = jQuery('.mwishlist-active-tab');
        body.insertAfter(activeTab);
        var toHeight = body.height();
        body.css({height: '0px'});
        
		bodyTemp.animate({ height: '0px' }, {
			easing: 'linear',
			duration: this.tabAccordionSpeed,
            complete: function(){ bodyTemp.remove() }
		});
        
		body.animate({ height: toHeight + 'px' }, {
			easing: 'linear',
			duration: this.tabAccordionSpeed,
            complete: function(){ body.css({height: 'auto'}); },
            progress: function(){ window.itorisMultipleWishlists.scrollToTab(activeTab) }
		});
    },
    
    updateTotal: function(){
        var total = 0;
        jQuery('#wishlist-view-form [data-price-type="finalPrice"]').each(function(index, item){
           var qtyBox = jQuery(item).closest('.mwishlist-item').find('input.qty');
           var qty = qtyBox[0] ? qtyBox.val() : 1;
           total += jQuery(item).attr('data-price-amount') * qty; 
        });
        if (total == 0) {
            jQuery('.mwishlist-totals').remove();
        } else {
            var prevTotal = jQuery('.mwishlist-totals .price').html();
            jQuery('.mwishlist-totals .price').text(prevTotal.replace(/[\d+|\.\ \,]/g, '')+total.toFixed(2));
        }
    },
    
    scrollToTab: function(tab) {
        var rect = tab[0].getBoundingClientRect();
        var isTabInViewPort = rect.top >= 0 && rect.bottom <= jQuery(window).height();
        if (!isTabInViewPort) tab[0].scrollIntoView();
    },
    
	getUrlParams: function(){
		var params={};
		window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(str,key,value) {
			params[key] = decodeURIComponent(value.replace(/\+/g, ' '));
		});
		return params;
	},
	
	renameWishlist: function() {
		if (!jQuery('#mwishlist_name').val()) {
			alert('The wishlist name can\'t be left blank');
			return;
		}
		this.mask.removeClass('black').show();
		this.loader.show();
		var tab = jQuery('.mwishlist-active-tab'), _this = window.itorisMultipleWishlists;
		jQuery.ajax({
			type: 'POST',
			url: jQuery('#wishlist-view-form')[0].action,
			data: {
					mwishlist_id: tab[0].tabid,
					mwishlist_action: 'rename_wishlist',
					mwishlist_name: jQuery('#mwishlist_name').val(),
                    form_key: jQuery('input[name="form_key"]')[0] ? jQuery('input[name="form_key"]')[0].value : ''
				},
			dataType: 'json',
			success: function(data){
				_this.mask.hide();
				_this.loader.hide();
				if (data.error) {
					alert(data.error);
				} else if (data.wishlist_name) {
					jQuery('.mwishlist-active-tab .mwishlist-tab-title').text(data.wishlist_name);
					jQuery('#mwishlist_name').val(data.wishlist_name);
					tab[0].tabname = data.wishlist_name;
					var url = document.location.href, pos = url.indexOf('?'), base = pos > -1 ? url.substr(0, pos) : url;
					window.history.pushState({}, null, tab[0].tabname ? '?mwishlist='+escape(tab[0].tabname) : base);
					_this.updateTabArrows();
				}
			}
		});
	},
	
	removeWishlist: function(){
		if (!window.confirm('Are you sure want to remove this wishlist?')) return;
		this.mask.removeClass('black').show();
		this.loader.show();
		var tab = jQuery('.mwishlist-active-tab'), _this = window.itorisMultipleWishlists;
		jQuery.ajax({
			type: 'POST',
			url: jQuery('#wishlist-view-form')[0].action,
			data: {
					mwishlist_id: tab[0].tabid,
					mwishlist_action: 'remove_wishlist',
                    form_key: jQuery('input[name="form_key"]')[0] ? jQuery('input[name="form_key"]')[0].value : ''
				},
			dataType: 'json',
			success: function(data){
				_this.mask.hide();
				_this.loader.hide();
				if (data.error) {
					alert(data.error);
				} else if (data.success) {
					jQuery('#wishlist-view-form').css({visibility: 'hidden'});
					var tabs = jQuery('.mwishlist-tab'), index = tabs.index(tab);
					var nextIndex = tabs[index + 1] ? index + 1 : index - 1;
					_this.switchTab(jQuery(tabs[nextIndex]), true);
					tab.remove();
					_this.updateTabArrows();
				}
			}
		});
	},
	
	createWishlist: function(){
		var field = jQuery('#mwishlist-create'), name = jQuery.trim(field.val()), _this = window.itorisMultipleWishlists;
		if (name == '' || name == field.attr('title')) {
			alert('Please enter a new wishlist name');
			field.focus();
			return;
		}
		_this.mask.removeClass('black').show();
		_this.loader.show();
		jQuery.ajax({
			type: 'POST',
			url: jQuery('#wishlist-view-form')[0].action,
			data: {
					mwishlist_name: name,
					mwishlist_action: 'create_wishlist',
                    form_key: jQuery('input[name="form_key"]')[0] ? jQuery('input[name="form_key"]')[0].value : ''
				},
			dataType: 'json',
			success: function(data){
				_this.mask.hide();
				_this.loader.hide();
                _this.popupBox._html = data.popup_html;
				if (data.error) {
					alert(data.error);
				} else if (data.success) {
					_this.wishlists[data.wishlist.id] = data.wishlist;
					_this.switchTab( _this.createTab(data.wishlist.id, data.wishlist), true );
					_this.updateTabArrows();
					jQuery('#mwishlist-create').val('').blur();
				}
			}
		});
	},
	
	removeItem: function(item) {
		if (!confirm('Do you really want to remove this item?')) return;
		var postData = JSON.parse(item.attr('orig-data-post-remove')), _this = this;
		_this.mask.removeClass('black').show();
		_this.loader.show();
        if (!postData.data.form_key && jQuery('input[name="form_key"]')[0]) postData.data.form_key = jQuery('input[name="form_key"]')[0].value;
		jQuery.ajax({
			type: 'POST',
			url: postData.action,
			data: postData.data,
			success: function(data){
				_this.mask.hide();
				_this.loader.hide();
				jQuery(item).closest('.mwishlist-item').animate({opacity:0, height:0}, {complete: function(){
					this.remove();
					jQuery('.mwishlist-active-tab .mwishlist-tab-qty').text('('+jQuery('.mwishlist-item').length+')');
					jQuery('.mwishlist-popup label[for="mwishlist_id'+jQuery('.mwishlist-active-tab')[0].tabid+'"] .mwishlist_qty').text('('+jQuery('.mwishlist-item').length+')');
					if (!jQuery('.mwishlist-item')[0]) _this.actionToolbar.detach();
                    _this.updateTotal();
				}});
			}
		});
	},
	
	copyItem: function(link, itemId) {
		var _this = this;
		this.activeLink = jQuery(link);
		this.activeLink.attr({
			'orig-data-post': JSON.stringify({
				action: jQuery('#wishlist-view-form')[0].action,
				data: {
					mwishlist_action: 'copy_item',
					item_id: itemId,
					mwishlist_from: jQuery('.mwishlist-active-tab')[0].tabid
				}
			})
		});
		this.activeContainer = this.popupBox;
		this.showPopup({headerText: 'Copy item to wishlist', buttonText: 'Copy Item', complete: function(data){
			jQuery.each(data.wishlists, function(index, wishlist){
				var tabFound = false;
				jQuery('.mwishlist-tab').each(function(i, tab){
					if (!tabFound && tab.tabid == wishlist.id) {
						jQuery(tab).find('.mwishlist-tab-qty').text('('+wishlist.qty+')');
						tabFound = true;
					}
					if (data.update_wishlists.indexOf(tab.tabid)) _this.tabCache[tab.tabid] = false;
				});
				if (!tabFound) {
					_this.createTab(index, wishlist);
					_this.updateTabArrows();
				}
			});
		}});
	},
	
	moveItem: function(link, itemId) {
		var _this = this;
		this.activeLink = jQuery(link);
		this.activeLink.attr({
			'orig-data-post': JSON.stringify({
				action: jQuery('#wishlist-view-form')[0].action,
				data: {
					mwishlist_action: 'move_item',
					item_id: itemId,
					mwishlist_from: jQuery('.mwishlist-active-tab')[0].tabid
				}
			})
		});
		this.activeContainer = this.popupBox;
		this.showPopup({headerText: 'Move item to wishlist', buttonText: 'Move Item', complete: function(data){
			jQuery(_this.activeLink).closest('.mwishlist-item').animate({opacity:0, height:0}, {complete: function(){
				this.remove();
				if (!jQuery('.mwishlist-item')[0]) _this.actionToolbar.detach();
                _this.updateTotal();
			}});
			jQuery.each(data.wishlists, function(index, wishlist){
				var tabFound = false;
				jQuery('.mwishlist-tab').each(function(i, tab){
					if (!tabFound && tab.tabid == wishlist.id) {
						jQuery(tab).find('.mwishlist-tab-qty').text('('+wishlist.qty+')');
						tabFound = true;
					}
					if (data.update_wishlists.indexOf(tab.tabid)) _this.tabCache[tab.tabid] = false;
				});
				if (!tabFound) {
					_this.createTab(index, wishlist);
					_this.updateTabArrows();
				}
			});
		}});
	},
	
	updateItemOptions: function(link) {
		link = jQuery(link.clone(false));
		var postData = JSON.parse(link.attr('orig-data-post'));
		jQuery("#product_addtocart_form").serializeArray().map(function(x){postData.data[x.name] = x.value;});
		jQuery("#product_addtocart_form").append(link.attr('data-post', JSON.stringify(postData)));
		this.mask.removeClass('black').show();
		this.loader.show();
		link.hide().click();
	},
    
    addAllToCart: function(ev) {
        ev.stopPropagation();
        var qtys = {};
        jQuery('#wishlist-view-form input[data-role="qty"]').each(function(index, qty){
            qtys[qty.name.replace('qty[', '').replace(']', '')] = qty.value;
        });
		var tab = jQuery('.mwishlist-active-tab'), _this = window.itorisMultipleWishlists;
		_this.mask.removeClass('black').show();
		_this.loader.show();
		jQuery.ajax({
			type: 'POST',
			url: jQuery('#wishlist-view-form')[0].action,
			data: {
					mwishlist_id: tab[0].tabid,
					mwishlist_action: 'add_all_to_cart',
                    qtys: JSON.stringify(qtys),
                    form_key: jQuery('input[name="form_key"]')[0] ? jQuery('input[name="form_key"]')[0].value : ''
				},
			dataType: 'json',
			success: function(data){
				_this.mask.hide();
				_this.loader.hide();
                
				_this.activeLink = jQuery(ev.target);
				_this.activeContainer = _this.popupBox;                
                _this.showPopup();
                _this.popupBox.html(''); 
				_this.popupBox._html = data.popup_html;
                if (data.removedItems) {
                    jQuery.each(data.removedItems, function(index, itemId){
                        jQuery('button[data-role="tocart"][data-item-id="'+itemId+'"]').closest('.mwishlist-item').animate({opacity:0, height:0}, {complete: function(){
                            this.remove();
                            jQuery('.mwishlist-active-tab .mwishlist-tab-qty').text('('+jQuery('.mwishlist-item').length+')');
                            jQuery('.mwishlist-popup label[for="mwishlist_id'+jQuery('.mwishlist-active-tab')[0].tabid+'"] .mwishlist_qty').text('('+jQuery('.mwishlist-item').length+')');
                            if (!jQuery('.mwishlist-item')[0]) _this.actionToolbar.detach();
                            _this.updateTotal();
                        }});
                    });
                }
                if (data.success) {
                    _this.popupBox.append('<div class="message success"><div>'+data.message+'</div></div>');
                }
                if (data.errors) {
                    jQuery.each(data.errors, function(index, msg){
                        _this.popupBox.append('<div class="message error"><div>'+msg+'</div></div>');
                    });
                }
                _this.updateTotal();
				_this.resize();
			}
		});
    },
    
    addToCart: function(ev) {
        var button = jQuery(ev.target);
        if (!button.is('button')) button = button.closest('button');
        ev.stopPropagation();
        var qtys = {}, itemId = button.attr('data-item-id');
        qtys[itemId] = jQuery('#mwishlist-tab-container input[name="qty['+itemId+']"]').val();
		var tab = jQuery('.mwishlist-active-tab'), _this = window.itorisMultipleWishlists;
		_this.mask.removeClass('black').show();
		_this.loader.show();
		jQuery.ajax({
			type: 'POST',
			url: jQuery('#wishlist-view-form')[0].action,
			data: {
					mwishlist_id: tab[0].tabid,
					mwishlist_action: 'add_to_cart',
                    qtys: JSON.stringify(qtys),
                    form_key: jQuery('input[name="form_key"]')[0] ? jQuery('input[name="form_key"]')[0].value : ''
				},
			dataType: 'json',
			success: function(data){
				_this.mask.hide();
				_this.loader.hide();
                
				_this.activeLink = button;
				_this.activeContainer = _this.popupBox;                
                _this.showPopup();
                _this.popupBox.html(''); 
				_this.popupBox._html = data.popup_html;
                if (data.removedItems) {
                    jQuery.each(data.removedItems, function(index, itemId){
                        jQuery('button[data-role="tocart"][data-item-id="'+itemId+'"]').closest('.mwishlist-item').animate({opacity:0, height:0}, {complete: function(){
                            this.remove();
                            jQuery('.mwishlist-active-tab .mwishlist-tab-qty').text('('+jQuery('.mwishlist-item').length+')');
                            jQuery('.mwishlist-popup label[for="mwishlist_id'+jQuery('.mwishlist-active-tab')[0].tabid+'"] .mwishlist_qty').text('('+jQuery('.mwishlist-item').length+')');
                            if (!jQuery('.mwishlist-item')[0]) _this.actionToolbar.detach();
                            _this.updateTotal();
                        }});
                    });
                }
                if (data.success) {
                    _this.popupBox.append('<div class="message success"><div>'+data.message+'</div></div>');
                }
                if (data.errors) {
                    jQuery.each(data.errors, function(index, msg){
                        _this.popupBox.append('<div class="message error"><div>'+msg+'</div></div>');
                    });
                }
                _this.updateTotal();
				_this.resize();
			}
		});
    },
    
    shareWishlist: function(ev) {
        ev.stopPropagation();
        jQuery(ev.target).css({'pointer-events': 'none'});
        var mwishlistId = jQuery('.mwishlist-active-tab')[0].tabid;
        var shareURL = jQuery('#wishlist-view-form')[0].action.replace('update', 'share'), cutPos = shareURL.indexOf('wishlist_id');
        if (cutPos > -1) shareURL = shareURL.substr(0, cutPos);
        if (mwishlistId) shareURL += 'mwishlist_id/' + mwishlistId+'/';
        document.location = shareURL;
    },
    
    updateWishlist: function(ev) {
        ev.stopPropagation();
        jQuery(ev.target).css({'pointer-events': 'none'});
        var _this = this, button = jQuery(ev.target), mwishlistId = jQuery('.mwishlist-active-tab')[0].tabid;
        if (!button.is('button')) button = button.closest('button');
		_this.mask.removeClass('black').show();
		_this.loader.show();
        jQuery.ajax({
            type: "POST",
            url: jQuery("#wishlist-view-form")[0].action,
            data: jQuery("#wishlist-view-form").serialize() + '&mwishlist_id=' + mwishlistId + '&mwishlist_action=update_wishlist',
            success: function(data) {
                jQuery('.mwishlist-items').html(jQuery('<div />').html(data).find('.mwishlist-items').html());
				_this.mask.hide();
				_this.loader.hide();
				_this.activeLink = button;
				_this.activeContainer = _this.popupBox;                
                _this.showPopup();
				_this.popupBox._html = _this.popupBox.html();
                _this.popupBox.html(''); 
                _this.updateTotal();
                _this.popupBox.append('<div class="message success"><div>Wishlist has been updated!</div></div>');
            }
        });
        jQuery(ev.target).css({'pointer-events': 'auto'});
    }
}