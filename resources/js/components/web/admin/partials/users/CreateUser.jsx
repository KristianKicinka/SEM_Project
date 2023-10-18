import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';

const CreateUser = ({show, handleClose, setFetchDataState}) => {

    const [name, setName] = useState("");
    const [surname, setSurname] = useState("");
    const [email, setEmail] = useState("");
    const [phone, setPhone] = useState("");
    const [password, setPassword] = useState("");
    const [role, setRole] = useState("basic_user");

    const [errors, setErrors] = useState({});
    const {http} = AuthUser();

    const createNewUser = async (e) => {
        e.preventDefault();

        const userData = {
            name:name, surname:surname, email:email, phone:phone, password:password, role:role
        };

        console.log(userData);

        try {
            let resp = await http.post('/admin/user/create', userData);
            console.log(resp);
            setFetchDataState(prevState => !prevState);
            clearInputs();
            handleClose();
        } catch (error) {
            console.log(error);
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    const clearInputs = () => {
        setName('');
        setSurname('');
        setEmail('');
        setRole('basic_user');
        setPassword('');
        setPhone('');
    }

    return (
        <Modal show={show} onHide={handleClose}>
            <Modal.Header closeButton>
                <Modal.Title>Create new user</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="container-fluid">
                    <div className="row">
                        <div className="col">
                            <form className="form" method="post" noValidate onSubmit={createNewUser} >
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
                                    <label htmlFor="user_role" className="text-dark">Select user role:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="User role select" 
                                        onChange={(e) => setRole(e.target.value)}
                                    >
                                        <option value="basic_user">Basic user</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </div>
                                <div className="form-group pt-3 text-center">
                                    <input 
                                        type="submit" 
                                        name="submit" 
                                        className="btn btn-search text-light btn-md col-md-10" 
                                        value="Create new user"/>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default CreateUser;