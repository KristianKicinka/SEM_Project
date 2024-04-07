/**
 * @file index.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React from 'react';
import ReactDOM from 'react-dom';

import Application from './components/Application';

if (document.getElementById('Application')) {
    ReactDOM.render(<Application />, document.getElementById('Application'));
}

localStorage.setItem("ActiveProcesses", JSON.stringify([]));