import React from "react";
import ReactDOM from "react-dom";
import axios from "axios";

const AppItem = ({ item, handleShowLoading, handleCloseLoading, handleShowResults, setResults, hashTypes}) => {


    const createHash = (fileName) =>{
        console.log(fileName);

        let data = {
            'file_name': fileName,
            'apk_type': 'downloaded',
            'hash_types': hashTypes
        }

        axios.post('/createHash', data).then( res => {
            console.log(res.data);
            setResults(res.data);
            handleCloseLoading();
            handleShowResults();
        });
    } 

    const getApkFile = (e) => {
        e.preventDefault();
        console.log(item.product_id);

        if(hashTypes.length === 0){
            console.log("Select hash type");
            return;
        }

        handleShowLoading();

        axios.post('/downloadApkFile', {'package_name': item.product_id}).then( res => {
            console.log(res.data);
            if(res.data != 'APK download failed!'){
                console.log('APK succesfully downloaded')
                createHash(res.data);
            }
                
        });
    };

    return (
        <a href={item.title} onClick={getApkFile} className="text-decoration-none" >
            <div className="card">
                <div className="card-body text-dark">
                    <div className="container">
                        <div className="row gx-2">
                            <div className="col-sm-4">
                                <img className="w-100" src={item.thumbnail} alt="AppIcon" />
                            </div>
                            <div className="col-sm-8">
                                <h6 className="card-title">{item.title}</h6>
                                <span className="card-text">
                                    {item.product_id}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    );
};

export default AppItem;
