import React, { useState } from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";
import AuthUser from "../../AuthUser";


const Login = () => {

    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");

    const {http, setToken} = AuthUser();
    const [errors, setErrors] = useState({});

    const loginUser = async (event) => {
        event.preventDefault();

        try {
            let resp = await http.post('/login', {email:email, password:password});
            setToken(resp.data.auth.token, resp.data.user);
        } catch (error) {
            if (error.response.status === 400)
                setErrors(error.response.data.errors);
            
            console.log(`ERROR: ${error}`);
        }
    }

    return (
        <div className="Login">
            <div className="navbar navbar-expand-lg navbar-dark bg-dark">
                <div className="container px-4">
                    <Link className="navbar-brand ps-3" to="/">
                        Mobile apps fingerprints generator
                    </Link>
                </div>
            </div>
            <div className="bg-primary bg-gradient d-flex min-vh-100 pt-5">
                <div className="container pt-5">
                    <div className="row justify-content-center align-items-center">
                        <div className="col-md-4 bg-white py-4 px-4 br-3 rounded-3">
                            <div className="col-md-12">
                                <form className="form" method="post" noValidate onSubmit={loginUser}>
                                    <h3 className="text-center text-dark py-2">Sign in</h3>
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
                                    <div className="form-group pt-3">
                                        <input 
                                            type="submit" 
                                            name="submit" 
                                            className="btn btn-search text-light btn-md col-md-3" 
                                            value="Login"/>
                                    </div>
                                    <div className="form-group pt-4">
                                        <small>
                                            Don't have an acoount? Register <Link to="/register" className="btn-link" >here</Link>. 
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

export default Login;