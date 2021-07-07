window.addEventListener('load', function(event) {
    var loadingWrapper = document.getElementById('loading-wrapper');
    loadingWrapper.classList.add('fadeOut');

    var root = document.getElementById('graphql-playground');
    root.classList.add('playgroundIn');

    var data = JSON.parse(document.getElementById('graphql-playground-data').innerText);
    GraphQLPlayground.init(root, {
        'endpoint': data.entrypoint
    })
});
