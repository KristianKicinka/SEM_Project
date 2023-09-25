import React from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";
import AuthUser from "../../../../AuthUser";

const Navbar = () => {

    const {token, logout} = AuthUser();

    const logoutUser = () => {
        if (token != undefined)
            logout();
    }

    return (
        <nav className="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow py-3">
            <div className="container">
                <div className="row w-100">
                    <div className="col-md-4"></div>
                    <div className="col-md-4"></div>
                    <div className="col-md-4">
                        <ul className="navbar-nav ml-auto float-end">
                            <li className="nav-item dropdown no-arrow show">
                                <span className="btn btn-link nav-link mr-2 d-none d-lg-inline text-gray-600 small" onClick={logoutUser}>
                                    <i className="fa-solid fa-right-from-bracket pe-1"></i> Logout
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    );
};

export default Navbar;
