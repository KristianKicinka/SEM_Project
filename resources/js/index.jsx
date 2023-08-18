import React from 'react';
import ReactDOM from 'react-dom';

import Application from './components/Application';

if (document.getElementById('Application')) {
    ReactDOM.render(<Application />, document.getElementById('Application'));
}

localStorage.setItem("ActiveProcesses",JSON.stringify([]));