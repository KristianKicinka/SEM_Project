import React from 'react';
import ReactDOM from 'react-dom';

import { BrowserRouter, Routes, Route } from "react-router-dom";

import MainPage from './web/MainPage';
import AboutPage from './web/AboutPage';
import APIPage from './web/APIPage';
import DatabasePage from './web/DatabasePage';


const Application = () => {
    return (
        <div className="Application">
            <BrowserRouter>
                <Routes>
                    <Route path='/' element={<MainPage/>} />
                    <Route path='/about' element={<AboutPage/>} />
                    <Route path='/api-info' element={<APIPage/>} />
                    <Route path='/database' element={<DatabasePage/>} />
                </Routes>
            </BrowserRouter>
        </div>
    );
}

export default Application;
