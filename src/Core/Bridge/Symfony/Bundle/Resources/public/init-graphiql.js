var initParameters = {};
var entrypoint = null;

function onEditQuery(newQuery) {
    initParameters.query = newQuery;
    updateURL();
}

function onEditVariables(newVariables) {
    initParameters.variables = newVariables;
    updateURL();
}

function onEditOperationName(newOperationName) {
    initParameters.operationName = newOperationName;
    updateURL();
}

function updateURL() {
    var newSearch = '?' + Object.keys(initParameters).filter(function (key) {
        return Boolean(initParameters[key]);
    }).map(function (key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(initParameters[key]);
    }).join('&');
    history.replaceState(null, null, newSearch);
}

function graphQLFetcher(graphQLParams) {
    return fetch(entrypoint, {
        method: 'post',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(graphQLParams),
        credentials: 'include'
    }).then(function (response) {
        return response.text();
    }).then(function (responseBody) {
        try {
            return JSON.parse(responseBody);
        } catch (error) {
            return responseBody;
        }
    });
}

window.onload = function() {
    var data = JSON.parse(document.getElementById('graphiql-data').innerText);
    entrypoint = data.entrypoint;

    var search = window.location.search;
    search.substr(1).split('&').forEach(function (entry) {
        var eq = entry.indexOf('=');
        if (eq >= 0) {
            initParameters[decodeURIComponent(entry.slice(0, eq))] = decodeURIComponent(entry.slice(eq + 1));
        }
    });

    if (initParameters.variables) {
        try {
            initParameters.variables = JSON.stringify(JSON.parse(initParameters.variables), null, 2);
        } catch (e) {
            // Do nothing, we want to display the invalid JSON as a string, rather than present an error.
        }
    }

    ReactDOM.render(
        React.createElement(GraphiQL, {
            fetcher: graphQLFetcher,
            query: initParameters.query,
            variables: initParameters.variables,
            operationName: initParameters.operationName,
            onEditQuery: onEditQuery,
            onEditVariables: onEditVariables,
            onEditOperationName: onEditOperationName
        }),
        document.getElementById('graphiql')
    );
}
