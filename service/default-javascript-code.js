const objRefs = {};
const fnRefs = {};
let cids = 1;

function encodeObject(obj) {
    return btoa(JSON.stringify(obj));
}

function decodeObject(objStr) {
    return JSON.parse(atob(objStr));
}

function isObject(obj, property, stop) {
    let proto = Object.getPrototypeOf(obj);
    while (proto && proto !== stop) {
        const desc = Object.getOwnPropertyDescriptor(proto, property);
        if (desc) {
            return typeof desc.value === 'object';
        }
        proto = Object.getPrototypeOf(proto);
    }
    return false;
}

function isMethod(obj, property, stop) {
    let proto = Object.getPrototypeOf(obj);
    while (proto && proto !== stop) {
        const desc = Object.getOwnPropertyDescriptor(proto, property);
        if (desc) {
            return typeof desc.value === 'function';
        }
        proto = Object.getPrototypeOf(proto);
    }
    return false;
}

function hasProperty(obj, name) {
    const desc = Object.getOwnPropertyDescriptor(obj, name);
    return !!desc;
}

function getInstancePropertiesNames(obj, stop) {
    let array = [];
    let proto = Object.getPrototypeOf(obj);
    while (proto && proto !== stop) {
        Object.getOwnPropertyNames(proto)
            .forEach(name => {
                if (name !== 'constructor') {
                    if (hasProperty(proto, name)) {
                        array.push(name);
                    }
                }
            });
        proto = Object.getPrototypeOf(proto);
    }
    return array;
}

class AbstractWebComponent extends HTMLElement {

    /** Lifecycle hooks names */
    _createLifecycleName = 'onCreate';
    _beforeRenderLifecycleName = 'beforeRender';
    _afterRenderLifecycleName = 'afterRender';

    _root;

    get cid() {
        return parseInt(this.getAttribute('cid'));
    }

    constructor(domMode) {
        super();
        this._domMode = domMode;
        this._onCreate();
        this._createRoot();
    }

    _createRoot() {
        if (this._domMode === 'shadow') {
            this._root = this.attachShadow({ mode: 'open' });
        } else {
            this._root = this;
        }
    }

    /** Rendering logic */
    render() {
        this._onBeforeRender();
        this._root.innerHTML = `
            ${this._compileStyle()}
            ${this._compileTemplate()}
        `;
        this._onAfterRender();
    }

    /** Lifecycle hooks */
    _onCreate() {
        this._runLifecycle(this._createLifecycleName);
    }

    _onBeforeRender() {
        this._runLifecycle(this._beforeRenderLifecycleName);
    }

    _onAfterRender() {
        this._runLifecycle(this._afterRenderLifecycleName);
    }

    _runLifecycle(lifecycle) {
        if (this[lifecycle]) {
            this[lifecycle]();
        }
    }

    /** Web component hooks */
    connectedCallback() {
        this.setAttribute('cid', cids++);
        this._setDefaultValues();
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        let shouldRender = true;
        const hiddenPropertyDescriptor = Object.getOwnPropertyDescriptor(this, `${name}_`);
        if (hiddenPropertyDescriptor && typeof hiddenPropertyDescriptor.value === 'object') {
            this[name + '_'] = decodeObject(newValue);
        }
        if (this.renderOnChanged) {
            shouldRender = this.renderOnChanged(name, oldValue, newValue);
        }
        if (shouldRender) {
            this.render();
        }
    }

    _createMethodRef(method, name) {
        const fnRefName = this._createIdentifier(name);
        fnRefs[fnRefName] = method;
        return fnRefName;
    }

    _createObjectRef(object, name) {
        const objRefName = this._createIdentifier(name);
        objRefs[objRefName] = object;
        return objRefName;
    }

    _createIdentifier(name) {
        return `${this.cid}#${name}`;
    }

    emit(event, eventDetails = {}) {
        if (typeof event === 'Event') {
            this._emitEvent(event);
        } else {
            const customEvent = new CustomEvent(event, eventDetails);
            this._emitEvent(customEvent);
        }
    }

    _emitEvent(event) {
        const listenerStr = this.getAttribute(`on${event.type}`);
        if (listenerStr) {
            const listenerExecutor = new Function('$event', `eval(${listenerStr})`);
            listenerExecutor(event);
        }
        this.dispatchEvent(event);
    }
}