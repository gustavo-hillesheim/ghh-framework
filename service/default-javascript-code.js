const objRefs = {};
const fnRefs = {};

class AbstractWebComponent extends HTMLElement {

    /** Lifecycle hooks names */
    _createLifecycleName = 'onCreate';
    _beforeRenderLifecycleName = 'beforeRender';
    _afterRenderLifecycleName = 'afterRender';

    _root;

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
        this._setDefaultValues();
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        let shouldRender = true;
        if (this.renderOnChanged) {
            shouldRender = this.renderOnChanged(name, oldValue, newValue);
        }
        if (shouldRender) {
            this.render();
        }
    }
}