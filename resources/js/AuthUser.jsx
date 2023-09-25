import React, { Component, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const AuthUser = () => {

    const getToken = () =>{
        const tokenString = sessionStorage.getItem('auth_token');
        const userToken = JSON.parse(tokenString);

        return userToken;
    };

    const getUser = () =>{
        const userString = sessionStorage.getItem('auth_user');
        const userDetail = JSON.parse(userString);

        return userDetail;
    };

    const navigate = useNavigate();
    const [token,setToken] = useState(getToken());
    const [user,setUser] = useState(getUser());

    const saveToken = (token, user) => {
        sessionStorage.setItem('auth_token',JSON.stringify(token));
        sessionStorage.setItem('auth_user',JSON.stringify(user));

        setToken(token);
        setUser(user);

        if(user.role == 'admin')
            navigate('/admin/dashboard');
        if(user.role == 'basic_user')
            navigate('/user/dashboard');
    };

    const logout = () => {
        sessionStorage.clear();
        navigate('/login');
    }

    const http = axios.create({
        baseURL: `${import.meta.env.VITE_APP_URL}/api`,
        headers: {
            "Content-type" : "application/json",
            "Authorization" : `Bearer ${token}`
        }
    });

    return { setToken:saveToken, token, user, getToken, http, logout }
}

export default AuthUser;