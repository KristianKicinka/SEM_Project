/**
 * @file AuthUser.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import React, { Component, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';


const AuthUser = () => {

    /**
     * @brief The function ensures getting auth token
     * @returns User auth token
     */
    const getToken = () =>{
        const tokenString = sessionStorage.getItem('auth_token');
        const userToken = JSON.parse(tokenString);

        return userToken;
    };

    /**
     * @brief The function ensures getting user data
     * @returns User data
     */
    const getUser = () =>{
        const userString = sessionStorage.getItem('auth_user');
        const userDetail = JSON.parse(userString);

        return userDetail;
    };

    const navigate = useNavigate();
    const [token,setToken] = useState(getToken());
    const [user,setUser] = useState(getUser());

    /**
     * @brief The function ensures saving auth tokens to session storage
     * @param {*} token Auth token
     * @param {*} user User data
     */
    const saveToken = (token, user) => {
        sessionStorage.setItem('auth_token',JSON.stringify(token));
        sessionStorage.setItem('auth_user',JSON.stringify(user));

        setToken(token);
        setUser(user);

        handleTokenExpiry();

        if(user.role == 'admin')
            navigate('/admin/dashboard');
        if(user.role == 'basic_user')
            navigate('/user/dashboard');
    };

    /**
     * @brief The function ensures logging out users
     */
    const logout = () => {
        sessionStorage.clear();
        navigate('/login');
    }

    /**
     * @brief The function ensures checking auth token expiration
     * @param {*} token Auth token
     * @returns isExpired bool value
     */
    const isTokenExpired = (token) => {
        if (!token) return true;

        const decodedToken = JSON.parse(atob(token.split('.')[1]));
        const expiryTime = decodedToken.exp * 1000;
        const currentTime = Date.now();

        return currentTime > expiryTime;
    };

    /**
     * @brief The function ensures handling token expiration
     */
    const handleTokenExpiry = () => {
        const token = sessionStorage.getItem('auth_token');

        if (token && !isTokenExpired(token)) {
          setTimeout(handleTokenExpiry, 60000);
        } else {
          logout();
        }
    };

    // HTTP asxios object for JSON requests
    const http = axios.create({
        baseURL: `/api`,
        headers: {
            "Content-type" : "application/json",
            "Authorization" : `Bearer ${token}`
        }
    });

    // HTTP asxios object for files requests
    const http_file = axios.create({
        baseURL: `/api`,
        headers: {
            "Authorization" : `Bearer ${token}`
        }
    });

    return { setToken:saveToken, token, user, getToken, http, http_file, logout }
}

export default AuthUser;
