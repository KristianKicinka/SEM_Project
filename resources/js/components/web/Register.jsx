/**
 * @file Register.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";
import AuthUser from "../../AuthUser";

const Register = () => {

    const [name, setName] = useState("");
    const [surname, setSurname] = useState("");
    const [email, setEmail] = useState("");
    const [phone, setPhone] = useState("");
    const [password, setPassword] = useState("");
    const [passwordRe, setPasswordRe] = useState("");

    const [errors, setErrors] = useState({});
    const {http, setToken} = AuthUser();

    /**
     * @brief The function ensures user registration
     * @param {*} event OnClick event
     */
    const registerUser = async (event) => {
        event.preventDefault();

        const userData = {
            name:name, surname:surname, email:email, phone:phone, password:password, re_password:passwordRe
        };

        try {
            let resp = await http.post('/register', userData);
            setToken(resp.data.auth.token, resp.data.user);
        } catch (error) {
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
        }
    }

    // Component body
    return (
        <div className="Register">
            <div className="navbar navbar-expand-lg navbar-dark bg-dark">
                <div className="container px-4">
                    <Link className="navbar-brand ps-3" to="/">
                    Mobile apps fingerprints generator
                    </Link>
                </div>
            </div>
            <div className="bg-primary bg-gradient pt-5 d-flex min-vh-100">
                <div className="container pt-5">
                    <div className="row justify-content-center align-items-center">
                        <div className="col-md-4 bg-white py-2 px-4 br-3 rounded-3">
                            <div className="col-md-12">
                                <form className="form" method="post" noValidate onSubmit={registerUser} >
                                    <h3 className="text-center text-dark py-2">Sign up</h3>
                                    <div className="form-group py-2">
                                        <label htmlFor="name" className="text-dark">Name:</label><br/>
                                        <input
                                            type="text"
                                            name="name"
                                            id="name"
                                            placeholder="name"
                                            value={name}
                                            onChange={(e) => setName(e.target.value)}
                                            className="form-control"/>
                                        {errors.name && <span className="error text-danger">{errors.name[0]}</span>}
                                    </div>
                                    <div className="form-group py-2">
                                        <label htmlFor="surname" className="text-dark">Surname:</label><br/>
                                        <input
                                            type="text"
                                            name="surname"
                                            id="surname"
                                            placeholder="surname"
                                            value={surname}
                                            onChange={(e) => setSurname(e.target.value)}
                                            className="form-control"/>
                                        {errors.surname && <span className="error text-danger">{errors.surname[0]}</span>}
                                    </div>
                                    <div className="form-group py-2">
                                        <label htmlFor="email" className="text-dark">E-mail:</label><br/>
                                        <input
                                            type="email"
                                            name="email"
                                            id="email"
                                            placeholder="email"
                                            value={email}
                                            onChange={(e) => setEmail(e.target.value)}
                                            className="form-control"/>
                                            {errors.email && <span className="error text-danger">{errors.email[0]}</span>}
                                    </div>
                                    <div className="form-group py-2">
                                        <label htmlFor="phone" className="text-dark">Phone number:</label><br/>
                                        <input
                                            type="text"
                                            name="phone"
                                            id="phone"
                                            placeholder="phone number"
                                            value={phone}
                                            onChange={(e) => setPhone(e.target.value)}
                                            className="form-control"/>
                                        {errors.phone && <span className="error text-danger">{errors.phone[0]}</span>}
                                    </div>
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
                                    <div className="form-group pt-3">
                                        <input
                                            type="submit"
                                            name="submit"
                                            className="btn btn-search text-light btn-md col-md-3"
                                            value="Register"/>
                                    </div>
                                    <div className="form-group pt-4">
                                        <small>
                                            Dou you have an account? Sign in <Link to="/login" className="btn-link" >here</Link>.
                                        </small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Register;
