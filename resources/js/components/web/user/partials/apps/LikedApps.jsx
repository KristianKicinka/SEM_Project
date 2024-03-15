import React, { useState, useEffect } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button, Form } from 'react-bootstrap';

const LikedApps = ({show, likedApps, handleClose, setFetchDataState}) => {

    const {http, token, user } = AuthUser();
    const [apps, setApps] = useState([]);

    const handleLikeToggle = async (appId, liked) => {

        const updatedApps = apps.map((app) => {
            if (app.id === appId) {
                console.log({ ...app, liked: !liked });
                return { ...app, liked: !liked };
            }
            return app;
        });
    
        setApps(updatedApps);

        try {
            let resp = await http.post('user/edit-liked-apps', {user_id:user.id, data:updatedApps});
            console.log(resp);
            setFetchDataState(prevState => !prevState);
        } catch (error) {
            console.log(error);
        }

      };

    useEffect(() => {
        setApps(likedApps)
    }, [likedApps]);

    return (
        <Modal show={show} onHide={handleClose} size="md" scrollable={true} >
            <Modal.Header closeButton>
                <Modal.Title>Liked apps</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="container-fluid">
                    <div className="row gap-3">
                        <ul class="list-group">
                        {apps.map((app, index) => (
                            <li key={index} class="list-group-item d-flex justify-content-between align-items-center">
                                {app.name}
                                <button className={app.liked ? 'liked' : 'unliked'} onClick={() => handleLikeToggle(app.id, app.liked)}>
                                {app.liked ? <i className="fas fa-heart"></i> : <i className="far fa-heart"></i>}
                                </button>
                            </li>
                        ))}
                        </ul>
                    </div>
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default LikedApps;