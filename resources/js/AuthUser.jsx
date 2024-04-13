/**
 * @file AuthUser.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import {useState} from 'react';
import {useNavigate} from 'react-router-dom';
import axios from 'axios';


const AuthUser = () => {

    let manualLogout = false;
    let timeoutID;

    /**
     * @brief The function ensures getting auth token
     * @returns User auth token
     */
    const getToken = () =>{
        const tokenString = sessionStorage.getItem('auth_token');
        return JSON.parse(tokenString);
    };

    /**
     * @brief The function ensures getting user data
     * @returns User data
     */
    const getUser = () => {
        const userString = sessionStorage.getItem('auth_user');
        return JSON.parse(userString);
    };

    /**
     * @brief The function ensures editting user data
     * @param {*} user New user data
     * @returns User data
     */
    const editUser = (user) => {
        sessionStorage.setItem('auth_user',JSON.stringify(user));
        setUser(user);
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

        if(user.role === 'admin')
            navigate('/admin/dashboard');
        if(user.role === 'basic_user')
            navigate('/user/dashboard');
    };

    /**
     * @brief The function ensures logging out users
     */
    const logout = () => {
        manualLogout = true;

        let timeout = Number(sessionStorage.getItem('expiry_timeout'));
        sessionStorage.clear();
        clearTimeout(timeout);
        navigate('/login');
    }

    /**
     * @brief The function ensures checking auth token expiration
     * @param {*} token Auth token
     * @returns isExpired bool value
     */
    const isTokenExpired = (token) => {
        if (!token)
            return true;

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
          let timeout= setTimeout(handleTokenExpiry, 60000);
          sessionStorage.setItem('expiry_timeout', timeout.toString());
        } else if (!manualLogout) {
          logout();
        }
    };

    // HTTP axios object for JSON requests
    const http = axios.create({
        baseURL: `/api`,
        headers: {
            "Content-type" : "application/json",
            "Authorization" : `Bearer ${token}`
        }
    });

    // HTTP axios object for files requests
    const http_file = axios.create({
        baseURL: `/api`,
        headers: {
            "Authorization" : `Bearer ${token}`
        }
    });

    return { setToken:saveToken, token, user, getToken, http, http_file, logout, editUser }
}

export default AuthUser;
