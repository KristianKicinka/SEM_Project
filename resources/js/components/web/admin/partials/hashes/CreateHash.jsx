/**
 * @file CreateHash.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import React, { useState } from "react";
import ReactDOM from "react-dom";

import AuthUser from "../../../../../AuthUser";
import { Modal, Button, Form } from 'react-bootstrap';


const CreateUser = ({show, handleClose, setFetchDataState}) => {

    const [appNameText, setAppNameText] = useState("");
    const [packageNameText, setPackageNameText] = useState("");
    const [appVersionText, setAppVersionText] = useState("");
    const [sni, setSni] = useState("");
    const [ipSrc, setIpSrc] = useState("");
    const [portSrc, setPortSrc] = useState("");
    const [ipDest, setIpDest] = useState("");
    const [portDest, setPortDest] = useState("");
    const [ja3Hash, setJa3Hash] = useState("");
    const [ja3sHash, setJa3sHash] = useState("");
    const [ja4Hash, setJa4Hash] = useState("");
    const [ja4sHash, setJa4sHash] = useState("");
    const [ja4xHash, setJa4xHash] = useState("");
    const [isMalwareText, setIsMalwareText] = useState(0);
    const [isDangerousText, setIsDangerousText] = useState(0);

    const [appNamePcap, setAppNamePcap] = useState("");
    const [packageNamePcap, setPackageNamePcap] = useState("");
    const [appVersionPcap, setAppVersionPcap] = useState("");
    const [pcapFile, setPcapFile] = useState(null);
    const [isMalwarePcap, setIsMalwarePcap] = useState(0);
    const [isDangerousPcap, setIsDangerousPcap] = useState(0);

    const [errors, setErrors] = useState({});
    const {http, http_file} = AuthUser();

    /**
     * @brief The function ensures creating hash from text input
     * @param {*} e Text input event
     */
    const createHashFromTextInput = async (e) => {
        e.preventDefault();

        const data = {
            app_name:appNameText,
            package_name:packageNameText,
            app_version:appVersionText,
            ja3_hash:ja3Hash,
            ja3s_hash:ja3sHash,
            sni:sni,
            ip_src:ipSrc,
            port_src:Number(portSrc),
            ip_dest:ipDest,
            port_dest:Number(portDest),
            ja4_hash:ja4Hash,
            ja4s_hash:ja4sHash,
            ja4x_hash:ja4xHash,
            is_malware:isMalwareText,
            is_dangerous:isDangerousText,
        };

        try {
            let resp = await http.post('/admin/hash/create/text-input', data);
            setFetchDataState(prevState => !prevState);
            //clearInputs();
            handleClose();
        } catch (error) {
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    /**
     * @brief The function ensures creating hashes from pcap file
     * @param {*} e Input file event
     */
    const createHashFromPcapFile = async (e) => {
        e.preventDefault();

        const data = new FormData();
        data.append("app_name_pcap", appNamePcap);
        data.append("package_name_pcap", packageNamePcap);
        data.append("app_version_pcap", appVersionPcap);
        data.append("is_malware_pcap", isMalwarePcap);
        data.append("is_dangerous_pcap", isDangerousPcap);
        data.append("pcap_file", pcapFile);

        try {
            let resp = await http_file.post('/admin/hash/create/pcap-file', data);
            setFetchDataState(prevState => !prevState);
            //clearInputs();
            handleClose();
        } catch (error) {
            if (error.response.status === 400) {
                setErrors(error.response.data.errors);
            }
            console.log(error);
        }
    }

    // Component body
    return (
        <Modal show={show} onHide={handleClose} dialogClassName="modal-80w" >
            <Modal.Header closeButton>
                <Modal.Title>Create new hash</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="container-fluid">
                    <div className="row gap-3">
                        <div className="col border rounded p-3">
                            <span className="text text-bold">Create hash from text input:</span>
                            <form className="form" method="post" noValidate onSubmit={createHashFromTextInput} >
                                <div className="row">
                                    <div className="col">
                                        <div className="form-group py-2">
                                            <label htmlFor="app_name" className="text-dark">App name:</label><br/>
                                            <input
                                                type="text"
                                                name="app_name"
                                                id="app_name"
                                                placeholder="app name"
                                                value={appNameText}
                                                onChange={(e) => setAppNameText(e.target.value)}
                                                className="form-control"/>
                                            {errors.app_name &&
                                                <span className="error text-danger">{errors.app_name[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="package_name" className="text-dark">Package
                                                name:</label><br/>
                                            <input
                                                type="text"
                                                name="package_name"
                                                id="package_name"
                                                placeholder="package_name"
                                                value={packageNameText}
                                                onChange={(e) => setPackageNameText(e.target.value)}
                                                className="form-control"/>
                                            {errors.package_name &&
                                                <span className="error text-danger">{errors.package_name[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="version" className="text-dark">Version:</label><br/>
                                            <input
                                                type="text"
                                                name="version"
                                                id="version"
                                                placeholder="version"
                                                value={appVersionText}
                                                onChange={(e) => setAppVersionText(e.target.value)}
                                                className="form-control"/>
                                            {errors.version &&
                                                <span className="error text-danger">{errors.version[0]}</span>}
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
                                            <label htmlFor="ip_src" className="text-dark">IP src:</label><br/>
                                            <input
                                                type="text"
                                                name="sni"
                                                id="ip_src"
                                                placeholder="IP src"
                                                value={ipSrc}
                                                onChange={(e) => setIpSrc(e.target.value)}
                                                className="form-control"/>
                                            {errors.ip_src && <span className="error text-danger">{errors.ip_src[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="port_src" className="text-dark">Port src:</label><br/>
                                            <input
                                                type="text"
                                                name="port_src"
                                                id="port_src"
                                                placeholder="port src"
                                                value={portSrc}
                                                onChange={(e) => setPortSrc(e.target.value)}
                                                className="form-control"/>
                                            {errors.port_src && <span className="error text-danger">{errors.port_src[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="ip_dest" className="text-dark">IP dest:</label><br/>
                                            <input
                                                type="text"
                                                name="ip_dest"
                                                id="ip_dest"
                                                placeholder="ip dest"
                                                value={ipDest}
                                                onChange={(e) => setIpDest(e.target.value)}
                                                className="form-control"/>
                                            {errors.ip_dest && <span className="error text-danger">{errors.ip_dest[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="port_dest" className="text-dark">Port dest:</label><br/>
                                            <input
                                                type="text"
                                                name="port_dest"
                                                id="port_dest"
                                                placeholder="port dest"
                                                value={portDest}
                                                onChange={(e) => setPortDest(e.target.value)}
                                                className="form-control"/>
                                            {errors.port_dest && <span className="error text-danger">{errors.port_dest[0]}</span>}
                                        </div>
                                    </div>
                                    <div className="col">
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
                                            {errors.ja3_hash &&
                                                <span className="error text-danger">{errors.ja3_hash[0]}</span>}
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
                                            {errors.ja3s_hash &&
                                                <span className="error text-danger">{errors.ja3s_hash[0]}</span>}
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
                                            {errors.ja4_hash &&
                                                <span className="error text-danger">{errors.ja4_hash[0]}</span>}
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
                                            {errors.ja4s_hash &&
                                                <span className="error text-danger">{errors.ja4s_hash[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="ja4x_hash" className="text-dark">JA4X hash:</label><br/>
                                            <input
                                                type="text"
                                                name="ja4x_hash"
                                                id="ja4x_hash"
                                                placeholder="ja4x_hash"
                                                value={ja4xHash}
                                                onChange={(e) => setJa4xHash(e.target.value)}
                                                className="form-control"/>
                                            {errors.ja4x_hash &&
                                                <span className="error text-danger">{errors.ja4x_hash[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="is_malware" className="text-dark">Is malware:</label><br/>
                                            <select
                                                className="form-select"
                                                aria-label="Select if is a malware"
                                                value={isMalwareText}
                                                onChange={(e) => setIsMalwareText(e.target.value)}>
                                                <option value={0}>is not malware</option>
                                                <option value={1}>malware</option>
                                            </select>
                                            {errors.is_malware &&
                                                <span className="error text-danger">{errors.is_malware[0]}</span>}
                                        </div>
                                        <div className="form-group py-2">
                                            <label htmlFor="is_dangerous" className="text-dark">Is
                                                dangerous:</label><br/>
                                            <select
                                                className="form-select"
                                                aria-label="Select if is a malware"
                                                value={isDangerousText}
                                                onChange={(e) => setIsDangerousText(e.target.value)}>
                                                <option value={0}>is not dangerous</option>
                                                <option value={1}>dangerous</option>
                                            </select>
                                            {errors.is_dangerous &&
                                                <span className="error text-danger">{errors.is_dangerous[0]}</span>}
                                        </div>
                                    </div>
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


                        <div className="col border rounded p-3">
                            <span className="text text-bold">Create hash from pcap file:</span>
                            <form className="form" method="post" noValidate onSubmit={createHashFromPcapFile}
                                  encType="multipart/form-data">
                                <div className="form-group py-2">
                                    <label htmlFor="app_name_pcap" className="text-dark">Application name:</label><br/>
                                    <input
                                        type="text"
                                        name="app_name_pcap"
                                        id="app_name_pcap"
                                        placeholder="Application name"
                                        value={appNamePcap}
                                        onChange={(e) => setAppNamePcap(e.target.value)}
                                        className="form-control"/>
                                    {errors.appNamePcap &&
                                        <span className="error text-danger">{errors.app_name_pcap[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="package_name_pcap" className="text-dark">Package name:</label><br/>
                                    <input
                                        type="text"
                                        name="package_name_pcap"
                                        id="package_name_pcap"
                                        placeholder="Package name"
                                        value={packageNamePcap}
                                        onChange={(e) => setPackageNamePcap(e.target.value)}
                                        className="form-control"/>
                                    {errors.package_name_pcap && <span className="error text-danger">{errors.package_name_pcap[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="app_version_pcap" className="text-dark">App version:</label><br/>
                                    <input
                                        type="text"
                                        name="app_version_pcap"
                                        id="app_version_pcap"
                                        placeholder="App version"
                                        value={appVersionPcap}
                                        onChange={(e) => setAppVersionPcap(e.target.value)}
                                        className="form-control"/>
                                    {errors.app_version_pcap && <span className="error text-danger">{errors.app_version_pcap[0]}</span>}
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_malware" className="text-dark">Is malware:</label><br/>
                                    <select
                                        className="form-select"
                                        aria-label="Select if is a malware"
                                        onChange={(e) => setIsMalwarePcap(e.target.value)}
                                    >
                                        <option value={0}>is not malware</option>
                                        <option value={1}>malware</option>
                                    </select>
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="is_dangerous" className="text-dark">Is dangerous:</label><br/>
                                    <select
                                        className="form-select"
                                        aria-label="Select if is a malware"
                                        onChange={(e) => setIsDangerousPcap(e.target.value)}
                                    >
                                        <option value={0}>is not dangerous</option>
                                        <option value={1}>dangerous</option>
                                    </select>
                                </div>
                                <div className="form-group py-2">
                                    <label htmlFor="hash" className="text-dark">Pcap file:</label><br/>
                                    <Form.Control type="file" className='col'
                                        onChange={e=>{setPcapFile(e.target.files[0])}} accept='.pcap' required />
                                    {errors.pcap_file && <span className="error text-danger">{errors.pcap_file[0]}</span>}
                                </div>
                                <div className="form-group pt-3 text-center">
                                    <input
                                        type="submit"
                                        name="submit"
                                        className="btn btn-search text-light btn-md col-md-10"
                                        value="Create new hash"/>
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
