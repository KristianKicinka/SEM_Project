import React from "react";
import ReactDOM from "react-dom";

import { Link } from "react-router-dom";

const Navbar = () => {
    return (
        <div className="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div className="container px-4">
                <Link className="navbar-brand ps-3" to="/">
                    Mobile apps fingerprints generator
                </Link>
                <button
                    className="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span className="navbar-toggler-icon"></span>
                </button>

                <div className="collapse navbar-collapse float-end" id="mainNavigation">
                    <ul className="navbar-nav ms-auto">
                        <li className="nav-item">
                            <Link className="nav-link active" aria-current="page" to="/about" >
                                About project
                            </Link>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link active" aria-current="page" to="/database" >
                                Fingerprints database
                            </Link>
                        </li>
                        <li className="nav-item">
                            <Link className="nav-link active" aria-current="page" to="/api-info" >
                                API
                            </Link>
                        </li>
                        <li className="nav-item ps-4 pt-1">
                            <Link className="btn btn-sm btn-search-outline" aria-current="page" to="/login" >
                                Sign in
                            </Link>
                        </li>
                        <li className="nav-item ps-2 pt-1">
                            <Link className="btn btn-sm btn-search text-white" aria-current="page" to="/register" >
                                Sign up
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
};

export default Navbar;
