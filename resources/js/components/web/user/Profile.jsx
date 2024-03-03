import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";
import { Link } from "react-router-dom";

import AuthUser from "../../../AuthUser";

import Navbar from "../partials/auth/Navbar";
import Sidebar from "../partials/auth/Sidebar";
import TableComponent from "../partials/TableComponent";


const Profile = () => {

    const { http, setToken, user } = AuthUser();

    const [apiKey, setApiKey] = useState("");

    const [name, setName] = useState(user.name);
    const [surname, setSurname] = useState(user.surname);
    const [email, setEmail] = useState(user.email);
    const [phone, setPhone] = useState(user.phone);
    const [password, setPassword] = useState("");
    const [passwordRe, setPasswordRe] = useState("");

    const [errors, setErrors] = useState({});
    const [fetchDataState, setFetchDataState] = useState(false);

    console.log(user);
    
    const editProfile = async (e) => {
        e.preventDefault();

        const userData = {
            name:name, surname:surname, email:email, phone:phone
        };

        try {
            let resp = await http.post('/edit', userData);
            console.log(resp);
            setToken(resp.data.auth.token, resp.data.user);
        } catch (error) {
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    const changePassword = (e) => {
        e.preventDefault();
    }

    const generateApiKey = async (e) => {
        e.preventDefault();
        try {
            let resp = await http.post('/user/api-key-generate', {user_id:user.id});
            setFetchDataState(prevState => !prevState);
            setApiKey(resp.data.api_auth_key ? resp.data.api_auth_key : "" );
        } catch (error) {
            console.log(error);
        }
    }

    const fetchData = async () => {
        try {
            let resp = await http.post('/user/get-api-key', {user_id:user.id});
            setApiKey(resp.data.api_auth_key ? resp.data.api_auth_key : "" );
        } catch (error) {
            console.log(error);
        }
    }

    useEffect(() => {
        fetchData();
        /*const interval = setInterval(() => {fetchData()}, 3000);
        return () => clearInterval(interval);*/
    }, [fetchDataState]);

    return (
        <div className="Dashboard container-fluid">
            <div className="row">
                <Sidebar sidebarType="basic_user" />
                <div className="col-md-10 px-0">
                    <Navbar />
                    <div className="page container pt-md-3">
                        <div className="container shadow bg-white text-dark p-3">
                            <div className="row p-3">
                                <div className="col">
                                    <h4 className="p-2">Profile</h4>
                                </div>
                                <div className="col"></div>
                                <div className="col"></div>
                            </div>
                            <div className="row p-3">
                                <div className="col-md-6">
                                    <form className="form" method="post" noValidate onSubmit={editProfile} >
                                        <h5 className="text-dark py-2">
                                            Edit user info
                                        </h5>

                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="form-group py-2">
                                                    <label htmlFor="name" className="text-dark" >
                                                        Name:
                                                    </label>
                                                    <br />
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        id="name"
                                                        placeholder="name"
                                                        value={name}
                                                        onChange={(e) =>
                                                            setName(e.target.value)
                                                        }
                                                        className="form-control"
                                                        />
                                                        {errors.name && (
                                                            <span className="error text-danger">
                                                                {errors.name[0]}
                                                            </span>
                                                        )}
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group py-2">
                                                    <label htmlFor="surname" className="text-dark" >
                                                        Surname:
                                                    </label>
                                                    <br />
                                                    <input
                                                        type="text"
                                                        name="surname"
                                                        id="surname"
                                                        placeholder="surname"
                                                        value={surname}
                                                        onChange={(e) =>
                                                            setSurname(e.target.value)
                                                        }
                                                        className="form-control"
                                                    />
                                                    {errors.surname && (
                                                        <span className="error text-danger">
                                                            {errors.surname[0]}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col-md-6">
                                                <div className="form-group py-2">
                                                    <label htmlFor="email" className="text-dark" >
                                                        E-mail:
                                                    </label>
                                                    <br />
                                                    <input
                                                        type="email"
                                                        name="email"
                                                        id="email"
                                                        placeholder="email"
                                                        value={email}
                                                        onChange={(e) =>
                                                            setEmail(e.target.value)
                                                        }
                                                        className="form-control"
                                                    />
                                                    {errors.email && (
                                                        <span className="error text-danger">
                                                            {errors.email[0]}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="col-md-6">
                                                <div className="form-group py-2">
                                                    <label htmlFor="phone" className="text-dark" >
                                                        Phone number:
                                                    </label>
                                                    <br />
                                                    <input
                                                        type="text"
                                                        name="phone"
                                                        id="phone"
                                                        placeholder="phone number"
                                                        value={phone}
                                                        onChange={(e) =>
                                                            setPhone(e.target.value)
                                                        }
                                                        className="form-control"
                                                    />
                                                    {errors.phone && (
                                                        <span className="error text-danger">
                                                            {errors.phone[0]}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div className="row">
                                            <div className="form-group pt-3">
                                                <input
                                                    type="submit"
                                                    name="submit"
                                                    className="btn btn-primary text-light btn-md col-md-3"
                                                    value="Save"
                                                />
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div className="col"></div>
                                <div className="col-md-5">
                                    <form className="form" method="post" noValidate onSubmit={editProfile} >
                                        <h5 className="text-dark py-2">
                                            Change password
                                        </h5>

                                        <div className="container-fluid">
                                            <div className="row">
                                                <div className="col-md-10">
                                                    <div className="form-group py-2">
                                                        <label htmlFor="password" className="text-dark">Password:</label><br/>
                                                        <input 
                                                            type="password"
                                                            name="password" 
                                                            id="password"
                                                            placeholder="password"
                                                            value={password}
                                                            onChange={(e) => setPassword(e.target.value)} 
                                                            className="form-control" />
                                                        {errors.password && <span className="error text-danger">{errors.password[0]}</span>}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="row">
                                                <div className="col-md-10">
                                                    <div className="form-group py-2">
                                                        <label htmlFor="re-password" className="text-dark">Re-password:</label><br/>
                                                        <input 
                                                            type="password"
                                                            name="re-password" 
                                                            id="re-password"
                                                            placeholder="re-password"
                                                            value={passwordRe}
                                                            onChange={(e) => setPasswordRe(e.target.value)} 
                                                            className="form-control" />
                                                        {errors.re_password && <span className="error text-danger">{errors.re_password[0]}</span>}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="row">
                                                <div className="form-group pt-3">
                                                    <input
                                                        type="submit"
                                                        name="submit"
                                                        className="btn btn-primary text-light btn-md col-md-3"
                                                        value="Save"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                            <div className="row p-3">
                                <div className="col-md-12 pt-2">
                                    <form className="form row g-3" method="post" noValidate onSubmit={generateApiKey}>
                                        <h5 className="text-dark py-2">
                                            Auth API key
                                        </h5>
                                        <div className="col-md-4">
                                            <input className="form-control" 
                                                   type="text" 
                                                   value={apiKey} 
                                                   aria-label="api key input"
                                                   placeholder="Auth API key" 
                                                   readOnly />
                                        </div>
                                        <div className="col-auto">
                                            <input
                                                type="submit" 
                                                name="submit" 
                                                className="btn btn-search text-light btn-md" 
                                                value="Generate API key" 
                                                />
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Profile;
