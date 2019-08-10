window.onload = function() {
    var data = JSON.parse(document.getElementById('graphiql-data').innerText);
    var entrypoint = data.entrypoint;
    var initParameters = {};

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

    function App() {
        var self = this;
        var graphiql = GraphiQL;

        var useStateParameters = React.useState(initParameters);
        var parameters = useStateParameters[0];
        var setParameters = useStateParameters[1];

        var useStateQuery = React.useState(parameters.query);
        var query = useStateQuery[0];
        var setQuery = useStateQuery[1];

        var useStateExplorerIsOpen = React.useState(true);
        var explorerIsOpen = useStateExplorerIsOpen[0];
        var setExplorerIsOpen = useStateExplorerIsOpen[1];

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

        function onEditQuery(newQuery) {
            setParameters(Object.assign(parameters, {query: newQuery}));
            setQuery(newQuery);
            updateURL();
        }

        function onEditVariables(newVariables) {
            setParameters(Object.assign(parameters, {variables: newVariables}));
            updateURL();
        }

        function onEditOperationName(newOperationName) {
            setParameters(Object.assign(parameters, {operationName: newOperationName}));
            updateURL();
        }

        function onRunOperation(operationName) {
            self.graphiql.handleRunQuery(operationName);
        }

        function updateURL() {
            var newSearch = '?' + Object.keys(parameters).filter(function (key) {
                return Boolean(parameters[key]);
            }).map(function (key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(parameters[key]);
            }).join('&');
            history.replaceState(null, null, newSearch);
        }

        function handleToggleExplorer() {
            setExplorerIsOpen(!explorerIsOpen)
        }

        function handlePrettifyQuery() {
            self.graphiql.handlePrettifyQuery();
        }

        function handleMergeQuery() {
            self.graphiql.handleMergeQuery();
        }

        function handleCopyQuery() {
            self.graphiql.handleCopyQuery();
        }

        function handleToggleHistory() {
            self.graphiql.handleToggleHistory();
        }

        return React.createElement(
            'div',
            {className: 'graphiql-container'},
            React.createElement(GraphiQLExplorer.Explorer, {
                fetcher: graphQLFetcher,
                query: query,
                onEdit: onEditQuery,
                onRunOperation: onRunOperation,
                explorerIsOpen: explorerIsOpen,
                onToggleExplorer: handleToggleExplorer
            }),
            React.createElement(
                GraphiQL,
                {
                    ref: function (ref) { self.graphiql = ref },
                    fetcher: graphQLFetcher,
                    query: query,
                    variables: parameters.variables,
                    operationName: parameters.operationName,
                    onEditQuery: onEditQuery,
                    onEditVariables: onEditVariables,
                    onEditOperationName: onEditOperationName
                },
                React.createElement(
                    GraphiQL.Toolbar,
                    {},
                    React.createElement(
                        GraphiQL.ToolbarButton,
                        {
                            onClick: handlePrettifyQuery,
                            label: 'Prettify',
                            title: 'Prettify Query (Shift-Ctrl-P)'
                        }
                    ),
                    React.createElement(
                        GraphiQL.ToolbarButton,
                        {
                            onClick: handleMergeQuery,
                            label: 'Merge',
                            title: 'Merge Query (Shift-Ctrl-M)'
                        }
                    ),
                    React.createElement(
                        GraphiQL.ToolbarButton,
                        {
                            onClick: handleCopyQuery,
                            label: 'Copy',
                            title: 'Copy Query (Shift-Ctrl-C)'
                        }
                    ),
                    React.createElement(
                        GraphiQL.ToolbarButton,
                        {
                            onClick: handleToggleHistory,
                            label: 'History',
                            title: 'Show History'
                        }
                    ),
                    React.createElement(
                        GraphiQL.ToolbarButton,
                        {
                            onClick: handleToggleExplorer,
                            label: 'Explorer',
                            title: 'Show Explorer'
                        }
                    )
                )
            )
        );
    }

    ReactDOM.render(React.createElement(App), document.getElementById('graphiql'));
}
