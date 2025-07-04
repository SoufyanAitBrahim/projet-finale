/*!
 * Bootstrap v5.3.0 Event Handler
 * Custom event system for Bootstrap components
 */

// Event handler utilities
const namespaceRegex = /[^.]*(?=\..*)\.|.*/;
const stripNameRegex = /\..*/;
const stripUidRegex = /::\d+$/;
const eventRegistry = {};
let uidEvent = 1;

const customEvents = {
  mouseenter: 'mouseover',
  mouseleave: 'mouseout'
};

const nativeEvents = new Set([
  'click', 'dblclick', 'mouseup', 'mousedown', 'contextmenu', 'mousewheel', 'DOMMouseScroll',
  'mouseover', 'mouseout', 'mousemove', 'selectstart', 'selectend', 'keydown', 'keypress',
  'keyup', 'orientationchange', 'touchstart', 'touchmove', 'touchend', 'touchcancel',
  'pointerdown', 'pointermove', 'pointerup', 'pointerleave', 'pointercancel', 'gesturestart',
  'gesturechange', 'gestureend', 'focus', 'blur', 'change', 'reset', 'select', 'submit',
  'focusin', 'focusout', 'load', 'unload', 'beforeunload', 'resize', 'move', 'DOMContentLoaded',
  'readystatechange', 'error', 'abort', 'scroll'
]);

function makeEventUid(element, uid) {
  return (uid && `${uid}::${uidEvent++}`) || element.uidEvent || uidEvent++;
}

function getElementEvents(element) {
  const uid = makeEventUid(element);
  element.uidEvent = uid;
  eventRegistry[uid] = eventRegistry[uid] || {};
  return eventRegistry[uid];
}

function bootstrapHandler(element, fn, delegationSelector = null) {
  return Object.values(element).find(event => event.callable === fn && event.delegationSelector === delegationSelector);
}

function normalizeParameters(originalTypeEvent, handler, delegationFunction) {
  const isDelegated = typeof originalTypeEvent === 'string';
  const callable = isDelegated ? delegationFunction : (handler || delegationFunction);
  let typeEvent = getTypeEvent(originalTypeEvent);
  
  if (!nativeEvents.has(typeEvent)) {
    typeEvent = originalTypeEvent;
  }
  
  return [isDelegated, callable, typeEvent];
}

function addHandler(element, originalTypeEvent, handler, delegationFunction, oneOff) {
  if (typeof originalTypeEvent !== 'string' || !element) {
    return;
  }
  
  let [isDelegated, callable, typeEvent] = normalizeParameters(originalTypeEvent, handler, delegationFunction);
  
  if (originalTypeEvent in customEvents) {
    const wrapFunction = fn => {
      return function (event) {
        if (!event.relatedTarget || (event.relatedTarget !== event.delegateTarget && !event.delegateTarget.contains(event.relatedTarget))) {
          return fn.call(this, event);
        }
      };
    };
    callable = wrapFunction(callable);
  }
  
  const events = getElementEvents(element);
  const handlers = events[typeEvent] || (events[typeEvent] = {});
  const previousFunction = bootstrapHandler(handlers, callable, isDelegated ? handler : null);
  
  if (previousFunction) {
    previousFunction.oneOff = previousFunction.oneOff && oneOff;
    return;
  }
  
  const uid = makeEventUid(callable, originalTypeEvent.replace(namespaceRegex, ''));
  const fn = isDelegated ? bootstrapDelegationHandler(element, handler, callable) : bootstrapHandler$1(element, callable);
  
  fn.delegationSelector = isDelegated ? handler : null;
  fn.callable = callable;
  fn.oneOff = oneOff;
  fn.uidEvent = uid;
  handlers[uid] = fn;
  
  element.addEventListener(typeEvent, fn, isDelegated);
}

function removeHandler(element, events, typeEvent, handler, delegationSelector) {
  const fn = bootstrapHandler(events[typeEvent], handler, delegationSelector);
  if (!fn) {
    return;
  }
  
  element.removeEventListener(typeEvent, fn, Boolean(delegationSelector));
  delete events[typeEvent][fn.uidEvent];
}

function removeNamespacedHandlers(element, events, typeEvent, namespace) {
  const storeElementEvent = events[typeEvent] || {};
  
  for (const [handlerKey, event] of Object.entries(storeElementEvent)) {
    if (handlerKey.includes(namespace)) {
      removeHandler(element, events, typeEvent, event.callable, event.delegationSelector);
    }
  }
}

function getTypeEvent(event) {
  event = event.replace(stripNameRegex, '');
  return customEvents[event] || event;
}

function bootstrapDelegationHandler(element, selector, fn) {
  return function handler(event) {
    const domElements = element.querySelectorAll(selector);
    
    for (let {target} = event; target && target !== this; target = target.parentNode) {
      for (const domElement of domElements) {
        if (domElement !== target) {
          continue;
        }
        
        hydrateObj(event, {delegateTarget: target});
        
        if (handler.oneOff) {
          EventHandler.off(element, event.type, selector, fn);
        }
        
        return fn.apply(target, [event]);
      }
    }
  };
}

function bootstrapHandler$1(element, fn) {
  return function handler(event) {
    hydrateObj(event, {delegateTarget: element});
    
    if (handler.oneOff) {
      EventHandler.off(element, event.type, fn);
    }
    
    return fn.apply(element, [event]);
  };
}

function hydrateObj(obj, meta = {}) {
  for (const [key, value] of Object.entries(meta)) {
    try {
      obj[key] = value;
    } catch {
      Object.defineProperty(obj, key, {
        configurable: true,
        get() {
          return value;
        }
      });
    }
  }
  
  return obj;
}

const EventHandler = {
  on(element, event, handler, delegationFunction) {
    addHandler(element, event, handler, delegationFunction, false);
  },
  
  one(element, event, handler, delegationFunction) {
    addHandler(element, event, handler, delegationFunction, true);
  },
  
  off(element, originalTypeEvent, handler, delegationFunction) {
    if (typeof originalTypeEvent !== 'string' || !element) {
      return;
    }
    
    const [isDelegated, callable, typeEvent] = normalizeParameters(originalTypeEvent, handler, delegationFunction);
    const inNamespace = typeEvent !== originalTypeEvent;
    const events = getElementEvents(element);
    const storeElementEvent = events[typeEvent] || {};
    const isNamespace = originalTypeEvent.startsWith('.');
    
    if (typeof callable !== 'undefined') {
      if (!Object.keys(storeElementEvent).length) {
        return;
      }
      
      removeHandler(element, events, typeEvent, callable, isDelegated ? handler : null);
      return;
    }
    
    if (isNamespace) {
      for (const elementEvent of Object.keys(events)) {
        removeNamespacedHandlers(element, events, elementEvent, originalTypeEvent.slice(1));
      }
    }
    
    for (const [keyHandlers, event] of Object.entries(storeElementEvent)) {
      const handlerKey = keyHandlers.replace(stripUidRegex, '');
      
      if (!inNamespace || originalTypeEvent.includes(handlerKey)) {
        removeHandler(element, events, typeEvent, event.callable, event.delegationSelector);
      }
    }
  },
  
  trigger(element, event, args) {
    if (typeof event !== 'string' || !element) {
      return null;
    }
    
    const $ = getjQuery();
    const typeEvent = getTypeEvent(event);
    const inNamespace = event !== typeEvent;
    
    let jQueryEvent = null;
    let bubbles = true;
    let nativeDispatch = true;
    let defaultPrevented = false;
    
    if (inNamespace && $) {
      jQueryEvent = $.Event(event, args);
      $(element).trigger(jQueryEvent);
      bubbles = !jQueryEvent.isPropagationStopped();
      nativeDispatch = !jQueryEvent.isImmediatePropagationStopped();
      defaultPrevented = jQueryEvent.isDefaultPrevented();
    }
    
    const evt = hydrateObj(new Event(event, {bubbles, cancelable: true}), args);
    
    if (defaultPrevented) {
      evt.preventDefault();
    }
    
    if (nativeDispatch) {
      element.dispatchEvent(evt);
    }
    
    if (evt.defaultPrevented && jQueryEvent) {
      jQueryEvent.preventDefault();
    }
    
    return evt;
  }
};

// Export EventHandler
window.BootstrapEventHandler = EventHandler;
