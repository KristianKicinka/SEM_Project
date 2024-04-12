/**
 * @file ProtectedRoute.jsx
 * @author Kristián Kičinka (xkicin02)
 *
 * @copyright Copyright (c) 2024
 */

import { Navigate} from "react-router-dom";
import AuthUser from "./AuthUser";

// Protected route component
const ProtectedRoute = ({children, userType}) =>{
    const {user} = AuthUser();

    if(!user)
        return <Navigate to="/login" state={{error: 'user_not_found'}} />;

    if(user.role !== userType)
        return <Navigate to="/login" state={{error: 'access_denied'}} />;

    return children;
}

export default ProtectedRoute;
