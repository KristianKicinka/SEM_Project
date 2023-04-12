import React, { useState } from "react";
import ReactDOM from "react-dom";

import Navbar from "./partials/Navbar";
import MainPageImage from "../../../../public/img/main_page.png";

const AboutPage = () => {
    return (
        <div className="AboutPage bg-primary bg-gradient pt-5 vh-100">
            <Navbar />
            <div className="container pt-5">
                <div className="row">
                    <div className="card bg-white text-dark p-3">
                        <div className="card-body">
                            <h3 className="card-title">About project</h3>
                            <div className="row">
                                <div className="col">
                                    <p className="card-text">
                                        The main idea of the project was to
                                        create an application that would enable
                                        the automated creation of TLS
                                        fingerprints from mobile applications
                                        created on the Android platform. The
                                        platform being developed will contribute
                                        to the improvement in the area of
                                        network traffic analysis, as well as to
                                        the increase in the efficiency of the
                                        work of network administrators. The
                                        above-mentioned platform is available in
                                        the form of a web application that can
                                        provide a native user interface to the
                                        user. The interface provides the user
                                        with several options for entering the
                                        android application for which
                                        fingerprints are to be generated. When
                                        specifying the types of fingerprints to
                                        be generated, the user has a choice of
                                        several variants, including JA3, JA3S
                                        and NetFlow. The platform also offers
                                        the possibility of registering already
                                        generated fingerprints of mobile
                                        applications through a database system.
                                        Last but not least, it is possible to
                                        use the created API to interact with the
                                        application.
                                    </p>
                                </div>
                                <div className="col">
                                    <img src={MainPageImage} className="w-100 shadow" alt="" />
                                </div>
                            </div>
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
