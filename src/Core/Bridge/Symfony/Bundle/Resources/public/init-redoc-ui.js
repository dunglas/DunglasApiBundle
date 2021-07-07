'use strict';

window.onload = () => {
    const data = JSON.parse(document.getElementById('swagger-data').innerText);

    Redoc.init(data.spec, {}, document.getElementById('swagger-ui'));
};
