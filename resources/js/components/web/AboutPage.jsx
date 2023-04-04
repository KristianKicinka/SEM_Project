import React, { useState } from "react";
import ReactDOM from "react-dom";

import Navbar from "./partials/Navbar";

const AboutPage = () => {
    return (
        <div className="AboutPage bg-primary bg-gradient pt-5 vh-100">
            <Navbar />
            <div className="container pt-5">
                <div className="row">
                    <div className="card bg-white text-dark p-3">
                        <div className="card-body">
                            <h5 className="card-title">About project</h5>
                            <p className="card-text">
                                Lorem ipsum dolor sit amet consectetur adipisicing elit.
                                Iste inventore adipisci eligendi nulla necessitatibus delectus
                                in nam iusto recusandae pariatur, natus eaque quas,
                                consectetur maxime laudantium fugit eos incidunt voluptatibus.
                            </p>
                            <a href="/" className="btn btn-search text-light">
                                Get started
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AboutPage;
