import React from 'react';
import ReactDOM from 'react-dom';

import { BrowserRouter, Routes, Route } from "react-router-dom";

import MainPage from './web/MainPage';
import AboutPage from './web/AboutPage';
import APIPage from './web/APIPage';
import DatabasePage from './web/DatabasePage';

import Login from './web/Login';
import Register from './web/Register';

import AdminUsers from './web/admin/Users';
import AdminHashes from './web/admin/Hashes';
import AdminSettings from './web/admin/Settings';
import AdminAPI from './web/admin/API';
import AdminDashboard from './web/admin/Dashboard';

import BasicUserDashboard from './web/user/Dashboard';
import BasicUserAPI from './web/user/API';
import BasicUserApps from './web/user/Apps';
import BasicUserProfile from './web/user/Profile';

import ProtectedRoute from '../ProtectedRoute';


const Application = () => {
    return (
        <div className="Application">
            <BrowserRouter>
                <Routes>
                    <Route path='/' element={<MainPage/>} />
                    <Route path='/about' element={<AboutPage/>} />
                    <Route path='/api-info' element={<APIPage/>} />
                    <Route path='/database' element={<DatabasePage/>} />

                    <Route path='/login' element={<Login/>} />
                    <Route path='/register' element={<Register/>} />

                    <Route path='/admin/dashboard' element={<ProtectedRoute userType='admin'><AdminDashboard/></ProtectedRoute>} />
                    <Route path='/admin/users' element={<ProtectedRoute userType='admin'><AdminUsers/></ProtectedRoute>} />
                    <Route path='/admin/hashes' element={<ProtectedRoute userType='admin'><AdminHashes/></ProtectedRoute>} />
                    <Route path='/admin/settings' element={<ProtectedRoute userType='admin'><AdminSettings/></ProtectedRoute>} />
                    <Route path='/admin/api' element={<ProtectedRoute userType='admin'><AdminAPI/></ProtectedRoute>} />

                    <Route path='/user/dashboard' element={<ProtectedRoute userType='basic_user'><BasicUserDashboard/></ProtectedRoute>} />
                    <Route path='/user/api' element={<ProtectedRoute userType='basic_user'><BasicUserAPI/></ProtectedRoute>} />
                    <Route path='/user/applications' element={<ProtectedRoute userType='basic_user'><BasicUserApps/></ProtectedRoute>} />
                    <Route path='/user/profile' element={<ProtectedRoute userType='basic_user'><BasicUserProfile/></ProtectedRoute>} />
                </Routes>
            </BrowserRouter>
        </div>
    );
}

export default Application;
