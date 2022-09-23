import React from 'react';
import ReactDOM from 'react-dom';

import {BrowserRouter, Routes, Route} from "react-router-dom";
import MainPage from './web/MainPage';

const Application = () => {
    return (
        <div className="Application">
            <BrowserRouter>
                <Routes>
                    <Route path='/' element={<MainPage/>} />
                </Routes>
            </BrowserRouter>
        </div>
    );
}

export default Application;
