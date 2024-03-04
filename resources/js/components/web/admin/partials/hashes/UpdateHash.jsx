import React, { useEffect, useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button } from 'react-bootstrap';

const UpdateHash = ({show, hash, handleClose, setFetchDataState}) => {

    console.log(hash);

    const [appName, setAppName] = useState('');
    const [packageName, setPackageName] = useState('');
    const [version, setVersion] = useState('');
    const [sni, setSni] = useState('');
    const [ja3Hash, setJa3Hash] = useState('');
    const [ja3sHash, setJa3sHash] = useState('');
    const [ja4Hash, setJa4Hash] = useState('');
    const [ja4sHash, setJa4sHash] = useState('');
    const [isDangerous, setIsDangerous] = useState(0);
    const [isMalware, setIsMalware] = useState(0);

    const [errors, setErrors] = useState({});
    const {http} = AuthUser();

    const updateHashData = async (e) => {
        e.preventDefault();

        const hashData = {
            hash_id:hash.id, app_name:appName, package_name:packageName,
            version:version, sni:sni, ja3_hash:ja3Hash, ja3s_hash:ja3sHash,
            ja4_hash:ja4Hash, ja4s_hash:ja4sHash, is_dangerous:isDangerous,
            is_malware:isMalware,
        };

        console.log(isDangerous);

        try {
            let resp = await http.post('/admin/hash/update', hashData);
            setFetchDataState(prevState => !prevState);
            handleClose();
        } catch (error) {
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    

    useEffect(() => {
        setAppName(hash?.app_name);
        setPackageName(hash?.package_name);
        setVersion(hash?.version);
        setSni(hash?.sni);
        setJa3Hash(hash?.ja3_hash);
        setJa3sHash(hash?.ja3s_hash);
        setJa4Hash(hash?.ja4_hash);
        setJa4sHash(hash?.ja4s_hash);
        setIsDangerous(hash?.is_dangerous);
        setIsMalware(hash?.is_malware);
    },[hash]);

    return (
        <Modal show={show} onHide={handleClose}>
            <Modal.Header closeButton>
                <Modal.Title>Update hash data</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="container-fluid">
                    <div className="row">
                        <div className="col">
                            <form className="form" method="post" noValidate onSubmit={updateHashData} >
                                <div className="form-group py-2">
                                    <label htmlFor="app_name" className="text-dark">App name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="app_name" 
                                        id="app_name"
                                        placeholder="app name"
                                        value={appName}
                                        onChange={(e) => setAppName(e.target.value)}
                                        className="form-control"/>
                                    {errors.app_name && <span className="error text-danger">{errors.app_name[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="package_name" className="text-dark">Package name:</label><br/>
                                    <input 
                                        type="text" 
                                        name="package_name" 
                                        id="package_name"
                                        placeholder="package_name"
                                        value={packageName}
                                        onChange={(e) => setPackageName(e.target.value)} 
                                        className="form-control"/>
                                    {errors.package_name && <span className="error text-danger">{errors.package_name[0]}</span>}
                                </div>           
                                <div className="form-group py-2">
                                    <label htmlFor="version" className="text-dark">Version:</label><br/>
                                    <input 
                                        type="text" 
                                        name="version" 
                                        id="version"
                                        placeholder="version" 
                                        value={version}
                                        onChange={(e) => setVersion(e.target.value)}
                                        className="form-control"/>
                                    {errors.version && <span className="error text-danger">{errors.version[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="sni" className="text-dark">SNI:</label><br/>
                                    <input 
                                        type="text" 
                                        name="sni" 
                                        id="sni"
                                        placeholder="sni" 
                                        value={sni}
                                        onChange={(e) => setSni(e.target.value)}
                                        className="form-control"/>
                                    {errors.sni && <span className="error text-danger">{errors.sni[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja3_hash" className="text-dark">JA3 hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja3_hash" 
                                        id="ja3_hash"
                                        placeholder="ja3_hash" 
                                        value={ja3Hash}
                                        onChange={(e) => setJa3Hash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja3_hash && <span className="error text-danger">{errors.ja3_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja3s_hash" className="text-dark">JA3S hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja3s_hash" 
                                        id="ja3s_hash"
                                        placeholder="ja3s_hash" 
                                        value={ja3sHash}
                                        onChange={(e) => setJa3sHash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja3s_hash && <span className="error text-danger">{errors.ja3s_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja4_hash" className="text-dark">JA4 hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja4_hash" 
                                        id="ja4_hash"
                                        placeholder="ja4_hash" 
                                        value={ja4Hash}
                                        onChange={(e) => setJa4Hash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja4_hash && <span className="error text-danger">{errors.ja4_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="ja4s_hash" className="text-dark">JA4S hash:</label><br/>
                                    <input 
                                        type="text" 
                                        name="ja4s_hash" 
                                        id="ja4s_hash"
                                        placeholder="ja4s_hash" 
                                        value={ja4sHash}
                                        onChange={(e) => setJa4sHash(e.target.value)}
                                        className="form-control"/>
                                    {errors.ja4s_hash && <span className="error text-danger">{errors.ja4s_hash[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_malware" className="text-dark">Is malware:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware"
                                        value={isMalware}
                                        onChange={(e) => setIsMalware(e.target.value)}
                                    >
                                        <option value={0}>is not malware</option>
                                        <option value={1}>malware</option>
                                    </select>
                                    {errors.is_malware && <span className="error text-danger">{errors.is_malware[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_dangerous" className="text-dark">Is dangerous:</label><br/>
                                    <select 
                                        className="form-select" 
                                        aria-label="Select if is a malware"
                                        value={isDangerous}
                                        onChange={(e) => setIsDangerous(e.target.value)}
                                    >
                                        <option value={0}>is not dangerous</option>
                                        <option value={1}>dangerous</option>
                                    </select>
                                    {errors.is_dangerous && <span className="error text-danger">{errors.is_dangerous[0]}</span>}
                                </div>
                                <div className="form-group pt-3 text-center">
                                    <input 
                                        type="submit" 
                                        name="submit" 
                                        className="btn btn-search text-light btn-md col-md-10" 
                                        value="Save data"/>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default UpdateHash;