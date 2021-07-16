'use strict';

window.onload = function() {
    manageWebbyDisplay();

    new MutationObserver(function (mutations, self) {
        const op = document.getElementById(`operations-${data.shortName}-${data.operationId}`);
        if (!op) return;

        self.disconnect();

        op.querySelector('.opblock-summary').click();
        const tryOutObserver = new MutationObserver(function (mutations, self) {
            const tryOut = op.querySelector('.try-out__btn');
            if (!tryOut) return;

            self.disconnect();

            tryOut.click();
            if (data.id) {
                const inputId = op.querySelector('.parameters input[placeholder="id"]');
                inputId.value = data.id;
                reactTriggerChange(inputId);
            }

            for (const input of op.querySelectorAll('.parameters input')) {
                if (input.placeholder in data.queryParameters) {
                    input.value = data.queryParameters[input.placeholder];
                    reactTriggerChange(input);
                }
            }

            // Wait input values to be populated before executing the query
            setTimeout(function(){
                op.querySelector('.execute').click();
                op.scrollIntoView();
            }, 500);
        });

        tryOutObserver.observe(document, {childList: true, subtree: true});
    }).observe(document, {childList: true, subtree: true});

    const data = JSON.parse(document.getElementById('swagger-data').innerText);
    const ui = SwaggerUIBundle(Object.assign({
        spec: data.spec,
        dom_id: '#swagger-ui',
        validatorUrl: null,
        oauth2RedirectUrl: data.oauth.redirectUrl,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset,
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl,
        ],
        layout: 'StandaloneLayout',
    }, data.extraConfiguration));

    if (data.oauth.enabled) {
        ui.initOAuth({
            clientId: data.oauth.clientId,
            clientSecret: data.oauth.clientSecret,
            realm: data.oauth.type,
            appName: data.spec.info.title,
            scopeSeparator: ' ',
            additionalQueryStringParams: {}
        });
    }

    // Workaround for https://github.com/swagger-api/swagger-ui/issues/3028
    // Adapted from https://github.com/vitalyq/react-trigger-change/blob/master/lib/change.js
    // Copyright (c) 2017 Vitaly Kuznetsov
    // MIT License
    function reactTriggerChange(node) {
        // Do not try to delete non-configurable properties.
        // Value and checked properties on DOM elements are non-configurable in PhantomJS.
        function deletePropertySafe(elem, prop) {
            const desc = Object.getOwnPropertyDescriptor(elem, prop);
            if (desc && desc.configurable) {
                delete elem[prop];
            }
        }

        // React 16
        // Cache artificial value property descriptor.
        // Property doesn't exist in React <16, descriptor is undefined.
        const descriptor = Object.getOwnPropertyDescriptor(node, 'value');

        // React 0.14: IE9
        // React 15: IE9-IE11
        // React 16: IE9
        // Dispatch focus.
        const focusEvent = document.createEvent('UIEvents');
        focusEvent.initEvent('focus', false, false);
        node.dispatchEvent(focusEvent);

        // React 0.14: IE9
        // React 15: IE9-IE11
        // React 16
        // In IE9-10 imperative change of node value triggers propertychange event.
        // Update inputValueTracking cached value.
        // Remove artificial value property.
        // Restore initial value to trigger event with it.
        const initialValue = node.value;
        node.value = initialValue + '#';
        deletePropertySafe(node, 'value');
        node.value = initialValue;

        // React 15: IE11
        // For unknown reason React 15 added listener for propertychange with addEventListener.
        // This doesn't work, propertychange events are deprecated in IE11,
        // but allows us to dispatch fake propertychange which is handled by IE11.
        const propertychangeEvent = document.createEvent('HTMLEvents');
        propertychangeEvent.initEvent('propertychange', false, false);
        propertychangeEvent.propertyName = 'value';
        node.dispatchEvent(propertychangeEvent);

        // React 0.14: IE10-IE11, non-IE
        // React 15: non-IE
        // React 16: IE10-IE11, non-IE
        const inputEvent = document.createEvent('HTMLEvents');
        inputEvent.initEvent('input', true, false);
        node.dispatchEvent(inputEvent);

        // React 16
        // Restore artificial value property descriptor.
        if (descriptor) {
            Object.defineProperty(node, 'value', descriptor);
        }
    }

    function manageWebbyDisplay() {
        const webby = document.getElementsByClassName('webby')[0];
        if (!webby) return;

        const web = document.getElementsByClassName('web')[0];
        webby.classList.add('calm');
        web.classList.add('calm');
        webby.addEventListener('click', () => {
            if (webby.classList.contains('frighten')) {
                return;
            }
            webby.classList.replace('calm', 'frighten');
            web.classList.replace('calm', 'frighten');
            setTimeout(() => {
                webby.classList.replace('frighten', 'calm');
                web.classList.replace('frighten', 'calm');
            }, 10000);
        });
    }
};
