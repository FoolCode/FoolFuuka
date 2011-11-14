/* ==========================================================
 * bootstrap-alerts.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#alerts
 * ==========================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */


!
function($) {

  "use strict"

/* CSS TRANSITION SUPPORT (https://gist.github.com/373874)
   * ======================================================= */

  var transitionEnd

  $(document).ready(function() {

    $.support.transition = (function() {
      var thisBody = document.body || document.documentElement,
          thisStyle = thisBody.style,
          support = thisStyle.transition !== undefined || thisStyle.WebkitTransition !== undefined || thisStyle.MozTransition !== undefined || thisStyle.MsTransition !== undefined || thisStyle.OTransition !== undefined
          
          return support
    })()

    // set CSS transition event type
    if ($.support.transition) {
      transitionEnd = "TransitionEnd"
      if ($.browser.webkit) {
        transitionEnd = "webkitTransitionEnd"
      } else if ($.browser.mozilla) {
        transitionEnd = "transitionend"
      } else if ($.browser.opera) {
        transitionEnd = "oTransitionEnd"
      }
    }

  })

/* ALERT CLASS DEFINITION
  * ====================== */

  var Alert = function(content, options) {
    this.settings = $.extend({}, $.fn.alert.defaults, options)
    this.$element = $(content).delegate(this.settings.selector, 'click', this.close)
  }

  Alert.prototype = {

    close: function(e) {
      var $element = $(this).parent('.alert-message')

      e && e.preventDefault()
      $element.removeClass('in')

      function removeElement() {
        $element.remove()
      }

      $.support.transition && $element.hasClass('fade') ? $element.bind(transitionEnd, removeElement) : removeElement()
    }

  }


/* ALERT PLUGIN DEFINITION
  * ======================= */

  $.fn.alert = function(options) {

    if (options === true) {
      return this.data('alert')
    }

    return this.each(function() {
      var $this = $(this)

      if (typeof options == 'string') {
        return $this.data('alert')[options]()
      }

      $(this).data('alert', new Alert(this, options))

    })
  }

  $.fn.alert.defaults = {
    selector: '.close'
  }

  $(document).ready(function() {
    new Alert($('body'), {
      selector: '.alert-message[data-alert] .close'
    })
  })

}(window.jQuery || window.ender);

/* ============================================================
 * bootstrap-buttons.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#buttons
 * ============================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================ */

!
function($) {

  "use strict"

  function setState(el, state) {
    var d = 'disabled',
        $el = $(el),
        data = $el.data()
        
        
         state = state + 'Text'
        
        data.resetText || $el.data('resetText', $el.html())
        
        
         $el.html(data[state] || $.fn.button.defaults[state])
        
        
         state == 'loadingText' ? $el.addClass(d).attr(d, d) : $el.removeClass(d).removeAttr(d)
  }

  function toggle(el) {
    $(el).toggleClass('active')
  }

  $.fn.button = function(options) {
    return this.each(function() {
      if (options == 'toggle') {
        return toggle(this)
      }
      options && setState(this, options)
    })
  }

  $.fn.button.defaults = {
    loadingText: 'loading...'
  }

  $(function() {
    $('body').delegate('.btn[data-toggle]', 'click', function() {
      $(this).button('toggle')
    })
  })

}(window.jQuery || window.ender);

/* =========================================================
 * bootstrap-modal.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#modal
 * =========================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================= */


!
function($) {

  "use strict"

/* CSS TRANSITION SUPPORT (https://gist.github.com/373874)
  * ======================================================= */

  var transitionEnd

  $(document).ready(function() {

    $.support.transition = (function() {
      var thisBody = document.body || document.documentElement,
          thisStyle = thisBody.style,
          support = thisStyle.transition !== undefined || thisStyle.WebkitTransition !== undefined || thisStyle.MozTransition !== undefined || thisStyle.MsTransition !== undefined || thisStyle.OTransition !== undefined
          
          return support
    })()

    // set CSS transition event type
    if ($.support.transition) {
      transitionEnd = "TransitionEnd"
      if ($.browser.webkit) {
        transitionEnd = "webkitTransitionEnd"
      } else if ($.browser.mozilla) {
        transitionEnd = "transitionend"
      } else if ($.browser.opera) {
        transitionEnd = "oTransitionEnd"
      }
    }

  })


/* MODAL PUBLIC CLASS DEFINITION
  * ============================= */

  var Modal = function(content, options) {
    this.settings = $.extend({}, $.fn.modal.defaults, options)
    this.$element = $(content).delegate('.close', 'click.modal', $.proxy(this.hide, this))

    if (this.settings.show) {
      this.show()
    }

    return this
  }

  Modal.prototype = {

    toggle: function() {
      return this[!this.isShown ? 'show' : 'hide']()
    }

    ,
    show: function() {
      var that = this
      this.isShown = true
      this.$element.trigger('show')

      escape.call(this)
      backdrop.call(this, function() {
        var transition = $.support.transition && that.$element.hasClass('fade')

        that.$element.appendTo(document.body).show()

        if (transition) {
          that.$element[0].offsetWidth // force reflow
        }

        that.$element.addClass('in')

        transition ? that.$element.one(transitionEnd, function() {
          that.$element.trigger('shown')
        }) : that.$element.trigger('shown')

      })

      return this
    }

    ,
    hide: function(e) {
      e && e.preventDefault()

      if (!this.isShown) {
        return this
      }

      var that = this
      this.isShown = false

      escape.call(this)

      this.$element.trigger('hide').removeClass('in')

      $.support.transition && this.$element.hasClass('fade') ? hideWithTransition.call(this) : hideModal.call(this)

      return this
    }

  }


/* MODAL PRIVATE METHODS
  * ===================== */

  function hideWithTransition() {
    // firefox drops transitionEnd events :{o
    var that = this,
        timeout = setTimeout(function() {
        that.$element.unbind(transitionEnd)
        hideModal.call(that)
      }, 500)
        
        
         this.$element.one(transitionEnd, function() {
        clearTimeout(timeout)
        hideModal.call(that)
      })
  }

  function hideModal(that) {
    this.$element.hide().trigger('hidden')

    backdrop.call(this)
  }

  function backdrop(callback) {
    var that = this,
        animate = this.$element.hasClass('fade') ? 'fade' : ''
        
        if (this.isShown && this.settings.backdrop) {
        var doAnimate = $.support.transition && animate

        this.$backdrop = $('<div class="modal-backdrop ' + animate + '" />').appendTo(document.body)

        if (this.settings.backdrop != 'static') {
          this.$backdrop.click($.proxy(this.hide, this))
        }

        if (doAnimate) {
          this.$backdrop[0].offsetWidth // force reflow
        }

        this.$backdrop.addClass('in')

        doAnimate ? this.$backdrop.one(transitionEnd, callback) : callback()

        } else if (!this.isShown && this.$backdrop) {
        this.$backdrop.removeClass('in')

        $.support.transition && this.$element.hasClass('fade') ? this.$backdrop.one(transitionEnd, $.proxy(removeBackdrop, this)) : removeBackdrop.call(this)

        } else if (callback) {
        callback()
        }
  }

  function removeBackdrop() {
    this.$backdrop.remove()
    this.$backdrop = null
  }

  function escape() {
    var that = this
    if (this.isShown && this.settings.keyboard) {
      $(document).bind('keyup.modal', function(e) {
        if (e.which == 27) {
          that.hide()
        }
      })
    } else if (!this.isShown) {
      $(document).unbind('keyup.modal')
    }
  }


/* MODAL PLUGIN DEFINITION
  * ======================= */

  $.fn.modal = function(options) {
    var modal = this.data('modal')

    if (!modal) {

      if (typeof options == 'string') {
        options = {
          show: /show|toggle/.test(options)
        }
      }

      return this.each(function() {
        $(this).data('modal', new Modal(this, options))
      })
    }

    if (options === true) {
      return modal
    }

    if (typeof options == 'string') {
      modal[options]()
    } else if (modal) {
      modal.toggle()
    }

    return this
  }

  $.fn.modal.Modal = Modal

  $.fn.modal.defaults = {
    backdrop: false,
    keyboard: false,
    show: false
  }


/* MODAL DATA- IMPLEMENTATION
  * ========================== */

  $(document).ready(function() {
    $('body').delegate('[data-controls-modal]', 'click', function(e) {
      e.preventDefault()
      var $this = $(this).data('show', true)
      $('#' + $this.attr('data-controls-modal')).modal($this.data())
    })
  })

}(window.jQuery || window.ender);

/* =============================================================
 * bootstrap-scrollspy.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#scrollspy
 * =============================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================== */


!
function($) {

  "use strict"

  var $window = $(window)

  function ScrollSpy(topbar, selector) {
    var processScroll = $.proxy(this.processScroll, this)
    this.$topbar = $(topbar)
    this.selector = selector || 'li > a'
    this.refresh()
    this.$topbar.delegate(this.selector, 'click', processScroll)
    $window.scroll(processScroll)
    this.processScroll()
  }

  ScrollSpy.prototype = {

    refresh: function() {
      this.targets = this.$topbar.find(this.selector).map(function() {
        var href = $(this).attr('href')
        return /^#\w/.test(href) && $(href).length ? href : null
      })

      this.offsets = $.map(this.targets, function(id) {
        return $(id).offset().top
      })
    }

    ,
    processScroll: function() {
      var scrollTop = $window.scrollTop() + 10,
          offsets = this.offsets,
          targets = this.targets,
          activeTarget = this.activeTarget,
          i
          
          
          
          for (i = offsets.length; i--;) {
          activeTarget != targets[i] && scrollTop >= offsets[i] && (!offsets[i + 1] || scrollTop <= offsets[i + 1]) && this.activateButton(targets[i])
          }
    }

    ,
    activateButton: function(target) {
      this.activeTarget = target

      this.$topbar.find(this.selector).parent('.active').removeClass('active')

      this.$topbar.find(this.selector + '[href="' + target + '"]').parent('li').addClass('active')
    }

  }

/* SCROLLSPY PLUGIN DEFINITION
   * =========================== */

  $.fn.scrollSpy = function(options) {
    var scrollspy = this.data('scrollspy')

    if (!scrollspy) {
      return this.each(function() {
        $(this).data('scrollspy', new ScrollSpy(this, options))
      })
    }

    if (options === true) {
      return scrollspy
    }

    if (typeof options == 'string') {
      scrollspy[options]()
    }

    return this
  }

  $(document).ready(function() {
    $('body').scrollSpy('[data-scrollspy] li > a')
  })

}(window.jQuery || window.ender);

/* ========================================================
 * bootstrap-tabs.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#tabs
 * ========================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ======================================================== */


!
function($) {

  "use strict"

  function activate(element, container) {
    container.find('> .active').removeClass('active').find('> .dropdown-menu > .active').removeClass('active')

    element.addClass('active')

    if (element.parent('.dropdown-menu')) {
      element.closest('li.dropdown').addClass('active')
    }
  }

  function tab(e) {
    var $this = $(this),
        $ul = $this.closest('ul:not(.dropdown-menu)'),
        href = $this.attr('href'),
        previous, $href
        
        
        
        if (/^#\w+/.test(href)) {
        e.preventDefault()

        if ($this.parent('li').hasClass('active')) {
          return
        }

        previous = $ul.find('.active a').last()[0]
        $href = $(href)

        activate($this.parent('li'), $ul)
        activate($href, $href.parent())

        $this.trigger({
          type: 'change',
          relatedTarget: previous
        })
        }
  }


/* TABS/PILLS PLUGIN DEFINITION
  * ============================ */

  $.fn.tabs = $.fn.pills = function(selector) {
    return this.each(function() {
      $(this).delegate(selector || '.tabs li > a, .pills > li > a', 'click', tab)
    })
  }

  $(document).ready(function() {
    $('body').tabs('ul[data-tabs] li > a, ul[data-pills] > li > a')
  })

}(window.jQuery || window.ender);

/* ==========================================================
 * bootstrap-twipsy.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#twipsy
 * Adapted from the original jQuery.tipsy by Jason Frame
 * ==========================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */


!
function($) {

  "use strict"

/* CSS TRANSITION SUPPORT (https://gist.github.com/373874)
  * ======================================================= */

  var transitionEnd

  $(document).ready(function() {

    $.support.transition = (function() {
      var thisBody = document.body || document.documentElement,
          thisStyle = thisBody.style,
          support = thisStyle.transition !== undefined || thisStyle.WebkitTransition !== undefined || thisStyle.MozTransition !== undefined || thisStyle.MsTransition !== undefined || thisStyle.OTransition !== undefined
          
          return support
    })()

    // set CSS transition event type
    if ($.support.transition) {
      transitionEnd = "TransitionEnd"
      if ($.browser.webkit) {
        transitionEnd = "webkitTransitionEnd"
      } else if ($.browser.mozilla) {
        transitionEnd = "transitionend"
      } else if ($.browser.opera) {
        transitionEnd = "oTransitionEnd"
      }
    }

  })


/* TWIPSY PUBLIC CLASS DEFINITION
  * ============================== */

  var Twipsy = function(element, options) {
    this.$element = $(element)
    this.options = options
    this.enabled = true
    this.fixTitle()
  }

  Twipsy.prototype = {

    show: function() {
      var pos, actualWidth, actualHeight, placement, $tip, tp

      if (this.hasContent() && this.enabled) {
        $tip = this.tip()
        this.setContent()

        if (this.options.animate) {
          $tip.addClass('fade')
        }

        $tip.remove().css({
          top: 0,
          left: 0,
          display: 'block'
        }).prependTo(document.body)

        pos = $.extend({}, this.$element.offset(), {
          width: this.$element[0].offsetWidth,
          height: this.$element[0].offsetHeight
        })

        actualWidth = $tip[0].offsetWidth
        actualHeight = $tip[0].offsetHeight

        placement = maybeCall(this.options.placement, this, [$tip[0], this.$element[0]])

        switch (placement) {
        case 'below':
          tp = {
            top: pos.top + pos.height + this.options.offset,
            left: pos.left + pos.width / 2 - actualWidth / 2
          }
          break
        case 'above':
          tp = {
            top: pos.top - actualHeight - this.options.offset,
            left: pos.left + pos.width / 2 - actualWidth / 2
          }
          break
        case 'left':
          tp = {
            top: pos.top + pos.height / 2 - actualHeight / 2,
            left: pos.left - actualWidth - this.options.offset
          }
          break
        case 'right':
          tp = {
            top: pos.top + pos.height / 2 - actualHeight / 2,
            left: pos.left + pos.width + this.options.offset
          }
          break
        }

        $tip.css(tp).addClass(placement).addClass('in')
      }
    }

    ,
    setContent: function() {
      var $tip = this.tip()
      $tip.find('.twipsy-inner')[this.options.html ? 'html' : 'text'](this.getTitle())
      $tip[0].className = 'twipsy'
    }

    ,
    hide: function() {
      var that = this,
          $tip = this.tip()
          
          
           $tip.removeClass('in')
          
          
           function removeElement() {
          $tip.remove()
          }
          
          
          
          $.support.transition && this.$tip.hasClass('fade') ? $tip.bind(transitionEnd, removeElement) : removeElement()
    }

    ,
    fixTitle: function() {
      var $e = this.$element
      if ($e.attr('title') || typeof($e.attr('data-original-title')) != 'string') {
        $e.attr('data-original-title', $e.attr('title') || '').removeAttr('title')
      }
    }

    ,
    hasContent: function() {
      return this.getTitle()
    }

    ,
    getTitle: function() {
      var title, $e = this.$element,
          o = this.options
          
          
           this.fixTitle()
          
          
           if (typeof o.title == 'string') {
          title = $e.attr(o.title == 'title' ? 'data-original-title' : o.title)
          } else if (typeof o.title == 'function') {
          title = o.title.call($e[0])
          }
          
          
          
          title = ('' + title).replace(/(^\s*|\s*$)/, "")
          
          
           return title || o.fallback
    }

    ,
    tip: function() {
      return this.$tip = this.$tip || $('<div class="twipsy" />').html(this.options.template)
    }

    ,
    validate: function() {
      if (!this.$element[0].parentNode) {
        this.hide()
        this.$element = null
        this.options = null
      }
    }

    ,
    enable: function() {
      this.enabled = true
    }

    ,
    disable: function() {
      this.enabled = false
    }

    ,
    toggleEnabled: function() {
      this.enabled = !this.enabled
    }

    ,
    toggle: function() {
      this[this.tip().hasClass('in') ? 'hide' : 'show']()
    }

  }


/* TWIPSY PRIVATE METHODS
  * ====================== */

  function maybeCall(thing, ctx, args) {
    return typeof thing == 'function' ? thing.apply(ctx, args) : thing
  }

/* TWIPSY PLUGIN DEFINITION
  * ======================== */

  $.fn.twipsy = function(options) {
    $.fn.twipsy.initWith.call(this, options, Twipsy, 'twipsy')
    return this
  }

  $.fn.twipsy.initWith = function(options, Constructor, name) {
    var twipsy, binder, eventIn, eventOut

    if (options === true) {
      return this.data(name)
    } else if (typeof options == 'string') {
      twipsy = this.data(name)
      if (twipsy) {
        twipsy[options]()
      }
      return this
    }

    options = $.extend({}, $.fn[name].defaults, options)

    function get(ele) {
      var twipsy = $.data(ele, name)

      if (!twipsy) {
        twipsy = new Constructor(ele, $.fn.twipsy.elementOptions(ele, options))
        $.data(ele, name, twipsy)
      }

      return twipsy
    }

    function enter() {
      var twipsy = get(this)
      twipsy.hoverState = 'in'

      if (options.delayIn == 0) {
        twipsy.show()
      } else {
        twipsy.fixTitle()
        setTimeout(function() {
          if (twipsy.hoverState == 'in') {
            twipsy.show()
          }
        }, options.delayIn)
      }
    }

    function leave() {
      var twipsy = get(this)
      twipsy.hoverState = 'out'
      if (options.delayOut == 0) {
        twipsy.hide()
      } else {
        setTimeout(function() {
          if (twipsy.hoverState == 'out') {
            twipsy.hide()
          }
        }, options.delayOut)
      }
    }

    if (!options.live) {
      this.each(function() {
        get(this)
      })
    }

    if (options.trigger != 'manual') {
      binder = options.live ? 'live' : 'bind'
      eventIn = options.trigger == 'hover' ? 'mouseenter' : 'focus'
      eventOut = options.trigger == 'hover' ? 'mouseleave' : 'blur'
      this[binder](eventIn, enter)[binder](eventOut, leave)
    }

    return this
  }

  $.fn.twipsy.Twipsy = Twipsy

  $.fn.twipsy.defaults = {
    animate: true,
    delayIn: 0,
    delayOut: 0,
    fallback: '',
    placement: 'above',
    html: false,
    live: false,
    offset: 0,
    title: 'title',
    trigger: 'hover',
    template: '<div class="twipsy-arrow"></div><div class="twipsy-inner"></div>'
  }

  $.fn.twipsy.rejectAttrOptions = ['title']

  $.fn.twipsy.elementOptions = function(ele, options) {
    var data = $(ele).data(),
        rejects = $.fn.twipsy.rejectAttrOptions,
        i = rejects.length
        
        
        
        while (i--) {
        delete data[rejects[i]]
        }
        
        
        
        return $.extend({}, options, data)
  }

}(window.jQuery || window.ender);

/* ===========================================================
 * bootstrap-popover.js v1.4.0
 * http://twitter.github.com/bootstrap/javascript.html#popover
 * ===========================================================
 * Copyright 2011 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * =========================================================== */


!
function($) {

  "use strict"

  var Popover = function(element, options) {
    this.$element = $(element)
    this.options = options
    this.enabled = true
    this.fixTitle()
  }

/* NOTE: POPOVER EXTENDS BOOTSTRAP-TWIPSY.js
     ========================================= */

  Popover.prototype = $.extend({}, $.fn.twipsy.Twipsy.prototype, {

    setContent: function() {
      var $tip = this.tip()
      $tip.find(this.options.titleSelector)[this.options.html ? 'html' : 'text'](this.getTitle())
      $tip.find(this.options.contentSelector)[this.options.html ? 'html' : 'text'](this.getContent())
      $tip[0].className = 'popover'
    }

    ,
    hasContent: function() {
      return this.getTitle() || this.getContent()
    }

    ,
    getContent: function() {
      var content, $e = this.$element,
          o = this.options
          
          
          
          if (typeof this.options.content == 'string') {
          content = $e.attr(this.options.content)
          } else if (typeof this.options.content == 'function') {
          content = this.options.content.call(this.$element[0])
          }
          
          
          
          return content
    }

    ,
    tip: function() {
      if (!this.$tip) {
        this.$tip = $('<div class="popover" />').html(this.options.template)
      }
      return this.$tip
    }

  })


/* POPOVER PLUGIN DEFINITION
  * ======================= */

  $.fn.popover = function(options) {
    if (typeof options == 'object') options = $.extend({}, $.fn.popover.defaults, options)
    $.fn.twipsy.initWith.call(this, options, Popover, 'popover')
    return this
  }

  $.fn.popover.defaults = $.extend({}, $.fn.twipsy.defaults, {
    placement: 'right',
    content: 'data-content',
    template: '<div class="arrow"></div><div class="inner"><h3 class="title"></h3><div class="content"><p></p></div></div>',
    titleSelector: '.title',
    contentSelector: '.content p'
  })

  $.fn.twipsy.rejectAttrOptions.push('content')

}(window.jQuery || window.ender);